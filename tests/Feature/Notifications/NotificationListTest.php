<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class ListTestUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}

class ListTestNotification extends BaseNotification
{
    public function via($n)
    {
        return ['database'];
    }

    public function toArray($n)
    {
        return ['title' => 'list.test'];
    }
}

beforeEach(function () {
    Livewire::addNamespace('synotif', __DIR__.'/../../../resources/views/components/notifications');
    view()->addNamespace('synapse', __DIR__.'/../../../resources/views');

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
    config(['synapps.apps.notifications.modal_per_page' => 2]);

    $this->user = ListTestUser::create([
        'name' => 'Ada', 'email' => 'ada-list@example.test', 'password' => bcrypt('secret'),
    ]);
    $this->actingAs($this->user);
});

afterEach(fn () => Schema::dropIfExists('notifications'));

it('paginates using the configured page size', function () {
    $this->user->notify(new ListTestNotification);
    $this->user->notify(new ListTestNotification);
    $this->user->notify(new ListTestNotification);

    Livewire::test('synotif::list')
        ->assertViewHas('items', fn ($items) => $items->count() === 2 && $items->total() === 3);
});

it('marks the loaded page as read immediately', function () {
    $this->user->notify(new ListTestNotification);

    Livewire::test('synotif::list');

    expect($this->user->notifications()->first()->read_at)->not->toBeNull();
});

it('marks one notification read manually', function () {
    $this->user->notify(new ListTestNotification);
    $id = $this->user->notifications()->first()->id;

    Livewire::test('synotif::list')->call('markOne', $id);

    expect($this->user->notifications()->first()->read_at)->not->toBeNull();
});

it('marks all as read', function () {
    $this->user->notify(new ListTestNotification);
    $this->user->notify(new ListTestNotification);

    Livewire::test('synotif::list')->call('markAll');

    expect($this->user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('puts the Livewire root on the outer list wrapper so items and pagination belong to the component', function () {
    $html = Livewire::test('synotif::list')->html();

    preg_match('/<[a-z]+\b[^>]*\bwire:id="[^"]*"[^>]*>/i', $html, $rootTag);

    expect($rootTag[0] ?? '')->toContain('synotif-modal-list');
});

it('still renders a single root element when notifications are disabled', function () {
    config(['synapps.apps.notifications.enabled' => false]);

    expect(Livewire::test('synotif::list')->html())->toMatch('/<div\b[^>]*\bwire:id="/');
});

it('renders without querying when notifications are disabled and the table does not exist', function () {
    config(['synapps.apps.notifications.enabled' => false]);
    Schema::dropIfExists('notifications');

    Livewire::test('synotif::list')
        ->assertOk()
        ->assertViewHas('items', null);
});
