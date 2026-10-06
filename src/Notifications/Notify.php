<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use VmEngine\SynAuth\Models\Role;

class Notify
{
    /** @var Collection<int|string, Model> */
    protected Collection $recipients;

    /** @var Collection<int|string, bool> */
    protected Collection $excepted;

    protected bool $viaQueue = false;

    protected function __construct(protected object $notification)
    {
        $this->recipients = collect();
        $this->excepted = collect();
    }

    public static function make(object $notification): self
    {
        return new self($notification);
    }

    /**
     * Target specific users. Accepts a model, a user id, or any iterable mix
     * of the two. An id that doesn't resolve to a user is warned and skipped
     * rather than throwing.
     */
    public function toUsers(mixed $users): self
    {
        foreach ($this->resolveUsers($users) as $key => $user) {
            $this->recipients->put($key, $user);
        }

        return $this;
    }

    /**
     * Target all users holding any of the given role slugs. Requires
     * vm-engine/synapps-auth. A role slug that doesn't exist is warned and
     * skipped rather than throwing; the package missing entirely throws,
     * since that's a structural misconfiguration, not a data state.
     *
     * @param  array<int, string>|string  $roles
     * @param  iterable<Model|int|string|null>  $except
     */
    public function toRoles(string|array $roles, iterable $except = []): self
    {
        if (! NotificationSetup::authInstalled()) {
            throw new RuntimeException(
                'Notify::toRoles() requires the vm-engine/synapps-auth package to be installed.'
            );
        }

        $roles = is_array($roles) ? $roles : [$roles];

        $existingSlugs = Role::whereIn('slug', $roles)->pluck('slug')->all();

        foreach (array_diff($roles, $existingSlugs) as $missing) {
            NotificationLog::warning("Notify::toRoles(): role \"{$missing}\" does not exist; skipped.");
        }

        $userModel = config('auth.providers.users.model');
        $found = $userModel::whereHas('roles', fn ($query) => $query->whereIn('slug', $roles))->get();

        foreach ($found as $user) {
            $this->recipients->put($user->getKey(), $user);
        }

        return $this->except($except);
    }

    /**
     * Exclude specific users from the recipient list. Accepts the same
     * shapes as toUsers() — a model, a user id, or an iterable mix — and
     * resolves ids the same way, so excepting by id actually works.
     */
    public function except(mixed $users): self
    {
        foreach ($this->resolveUsers($users) as $key => $user) {
            $this->excepted->put($key, true);
        }

        return $this;
    }

    public function withToastr(bool $toastr = true): self
    {
        if (property_exists($this->notification, 'toastr')) {
            $this->notification->toastr = $toastr;
        }

        return $this;
    }

    public function queued(bool $queued = true): self
    {
        $this->viaQueue = $queued;

        return $this;
    }

    public function send(): void
    {
        $recipients = $this->recipients->except($this->excepted->keys())->values();

        if ($this->viaQueue) {
            SendNotificationJob::dispatch($recipients, $this->notification);

            return;
        }

        static::deliver($recipients, $this->notification);
    }

    /**
     * Actually resolve the gate and send. Public+static so SendNotificationJob
     * can call it after deserializing off the queue.
     *
     * @param  Collection<int, Model>  $recipients
     */
    public static function deliver(Collection $recipients, object $notification): void
    {
        if (! NotificationGate::enabled()) {
            NotificationLog::info(sprintf(
                'Notification skipped (feature disabled): %s to %d recipient(s).',
                $notification::class,
                $recipients->count()
            ));

            return;
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, $notification);
    }

    /**
     * Resolve a model, a user id, or an iterable mix of the two into a
     * Collection<key, Model> keyed by primary key. Shared by toUsers() and
     * except() so both resolve ids identically. A null value or an id that
     * doesn't exist is warned and skipped rather than throwing.
     *
     * @return Collection<int|string, Model>
     */
    protected function resolveUsers(mixed $users): Collection
    {
        $resolved = collect();

        foreach ($this->toIterable($users) as $user) {
            if ($user instanceof Model) {
                $resolved->put($user->getKey(), $user);

                continue;
            }

            if ($user === null) {
                NotificationLog::warning('Notify: received a null user value; skipped.');

                continue;
            }

            $userModel = config('auth.providers.users.model');
            $found = $userModel::find($user);

            if (! $found) {
                NotificationLog::warning("Notify: user id \"{$user}\" does not exist; skipped.");

                continue;
            }

            $resolved->put($found->getKey(), $found);
        }

        return $resolved;
    }

    /**
     * @return iterable<Model|int|string|null>
     */
    protected function toIterable(mixed $value): iterable
    {
        if ($value === null) {
            return [];
        }

        if ($value instanceof Model) {
            return [$value];
        }

        if (is_iterable($value)) {
            return $value;
        }

        return [$value];
    }
}
