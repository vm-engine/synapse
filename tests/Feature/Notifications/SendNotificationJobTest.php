<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use VmEngine\Synapse\Notifications\Notify;
use VmEngine\Synapse\Notifications\SendNotificationJob;

class QueuedTestUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}

class QueuedTestNotification extends BaseNotification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'test.title'];
    }
}

beforeEach(function () {
    Schema::dropIfExists('notifications');
    Schema::create('notifications', function ($table) {
        $table->uuid('id')->primary();
        $table->string('type');
        $table->morphs('notifiable');
        $table->text('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });

    if (! Schema::hasTable('users')) {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    config(['synapps.apps.notifications.enabled' => true]);

    $this->user = QueuedTestUser::create([
        'name' => 'Ada', 'email' => 'ada-q@example.test', 'password' => bcrypt('secret'),
    ]);
});

afterEach(fn () => Schema::dropIfExists('notifications'));

it('dispatches a job instead of sending inline when queued() is used', function () {
    Bus::fake();

    Notify::make(new QueuedTestNotification)->toUsers($this->user)->queued()->send();

    Bus::assertDispatched(SendNotificationJob::class);
});

it('sends the notification when the dispatched job runs', function () {
    Notification::fake();

    (new SendNotificationJob(collect([$this->user]), new QueuedTestNotification))->handle();

    Notification::assertSentTo($this->user, QueuedTestNotification::class);
});
