<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use VmEngine\Synapse\Notifications\Concerns\InteractsWithNotifications;

class InteractsUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}

class InteractsHost
{
    use InteractsWithNotifications;

    public function callMarkOne(string $id): void
    {
        $this->markNotificationRead($id);
    }

    public function callMarkAll(): void
    {
        $this->markAllNotificationsRead();
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

    $this->user = InteractsUser::create([
        'name' => 'Ada', 'email' => 'ada-interacts@example.test', 'password' => bcrypt('secret'),
    ]);
    $this->actingAs($this->user);
});

afterEach(fn () => Schema::dropIfExists('notifications'));

it('renders a translated title with params from the stored data', function () {
    app('translator')->addLines(['test.hello' => 'Hello :name'], 'en');
    $notification = new DatabaseNotification;
    $notification->data = ['title' => 'test.hello', 'params' => ['name' => 'Ada']];

    $host = new InteractsHost;

    expect($host->renderTitle($notification))->toBe('Hello Ada');
});

it('marks a single notification as read by id', function () {
    $this->user->notify(new class extends Notification
    {
        public function via($n)
        {
            return ['database'];
        }

        public function toArray($n)
        {
            return ['title' => 'x'];
        }
    });

    $id = $this->user->notifications()->first()->id;
    (new InteractsHost)->callMarkOne($id);

    expect($this->user->notifications()->first()->read_at)->not->toBeNull();
});

it('marks all unread notifications as read', function () {
    $notification = new class extends Notification
    {
        public function via($n)
        {
            return ['database'];
        }

        public function toArray($n)
        {
            return ['title' => 'x'];
        }
    };
    $this->user->notify($notification);
    $this->user->notify($notification);

    (new InteractsHost)->callMarkAll();

    expect($this->user->fresh()->unreadNotifications()->count())->toBe(0);
});
