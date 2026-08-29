<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use VmEngine\Synapse\Notifications\Concerns\HasToastrFlag;
use VmEngine\Synapse\Notifications\Notify;

class NotifyTestUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}

class NotifyTestNotification extends BaseNotification
{
    use HasToastrFlag;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'test.title',
            'toastr' => $this->toastr ?? false,
        ];
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

    $this->user = NotifyTestUser::create([
        'name' => 'Ada',
        'email' => 'ada@example.test',
        'password' => bcrypt('secret'),
    ]);
});

afterEach(function () {
    Schema::dropIfExists('notifications');
});

it('sends to a directly-targeted user', function () {
    Notification::fake();

    Notify::make(new NotifyTestNotification)->toUsers($this->user)->send();

    Notification::assertSentTo($this->user, NotifyTestNotification::class);
});

it('warns and skips a nonexistent user id without throwing', function () {
    Notification::fake();
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('warning')->once()->with(Mockery::pattern('/user id "999999" does not exist/'));

    Notify::make(new NotifyTestNotification)->toUsers(999999)->toUsers($this->user)->send();

    Notification::assertSentTo($this->user, NotifyTestNotification::class);
});

it('does not send anything when the gate is disabled, and logs it', function () {
    config(['synapps.apps.notifications.enabled' => false]);
    Notification::fake();
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->once()->with(Mockery::pattern('/Notification skipped \(feature disabled\)/'));

    Notify::make(new NotifyTestNotification)->toUsers($this->user)->send();

    Notification::assertNothingSent();
});

it('excludes users passed to except() regardless of how they were added', function () {
    Notification::fake();
    $excluded = NotifyTestUser::create([
        'name' => 'Bob', 'email' => 'bob@example.test', 'password' => bcrypt('secret'),
    ]);

    Notify::make(new NotifyTestNotification)
        ->toUsers([$this->user, $excluded])
        ->except([$excluded])
        ->send();

    Notification::assertSentTo($this->user, NotifyTestNotification::class);
    Notification::assertNotSentTo($excluded, NotifyTestNotification::class);
});

it('excludes a user passed to except() by id, not just by model', function () {
    Notification::fake();
    $excluded = NotifyTestUser::create([
        'name' => 'Carol', 'email' => 'carol@example.test', 'password' => bcrypt('secret'),
    ]);

    Notify::make(new NotifyTestNotification)
        ->toUsers([$this->user, $excluded])
        ->except([$excluded->id])
        ->send();

    Notification::assertSentTo($this->user, NotifyTestNotification::class);
    Notification::assertNotSentTo($excluded, NotifyTestNotification::class);
});

it('sets the toastr flag on the notification when withToastr is used', function () {
    $notify = Notify::make(new NotifyTestNotification)->toUsers($this->user)->withToastr();

    $reflection = new ReflectionClass($notify);
    $property = $reflection->getProperty('notification');
    $property->setAccessible(true);

    expect($property->getValue($notify)->toastr)->toBeTrue();
});

it('throws when toRoles is called without synapps-auth installed', function () {
    Notify::make(new NotifyTestNotification)->toRoles('admin');
})->throws(RuntimeException::class, 'requires the vm-engine/synapps-auth package');
