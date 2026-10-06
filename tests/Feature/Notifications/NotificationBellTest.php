<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    // SynapseViewServiceProvider (which registers the synotif:: Livewire
    // namespace and the synapse:: view namespace, resolving <x-synapse::modal>
    // as an anonymous component) only boots when SynapseUtils::isSynAppsAutoloaded()
    // is true, which this package's TestCase deliberately doesn't set up.
    // Register just what these tests need, same pattern as
    // Unit/Components/BladeComponentsTest.php.
    Livewire::addNamespace('synotif', __DIR__.'/../../../resources/views/components/notifications');
    view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
});

class BellTestUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}

class BellTestNotification extends BaseNotification
{
    public function __construct(protected bool $toastr = false) {}

    public function via($n)
    {
        return ['database'];
    }

    public function toArray($n)
    {
        return ['title' => 'bell.test', 'toastr' => $this->toastr];
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

    $this->user = BellTestUser::create([
        'name' => 'Ada', 'email' => 'ada-bell@example.test', 'password' => bcrypt('secret'),
    ]);
    $this->actingAs($this->user);
});

afterEach(fn () => Schema::dropIfExists('notifications'));

it('shows the unread count', function () {
    $this->user->notify(new BellTestNotification);
    $this->user->notify(new BellTestNotification);

    Livewire::test('synotif::bell')
        ->assertSet('unreadCount', 2);
});

it('marks displayed notifications read on open but keeps them visually unread until reopened', function () {
    $this->user->notify(new BellTestNotification);
    $id = $this->user->notifications()->first()->id;

    $component = Livewire::test('synotif::bell')->call('openPanel');

    expect($this->user->notifications()->first()->read_at)->not->toBeNull();
    expect($component->get('visuallyUnreadIds'))->toContain($id);

    $component->call('openPanel');
    expect($component->get('visuallyUnreadIds'))->not->toContain($id);
});

it('marks one notification read manually and clears its visually-unread flag immediately', function () {
    $this->user->notify(new BellTestNotification);
    $id = $this->user->notifications()->first()->id;

    Livewire::test('synotif::bell')
        ->call('openPanel')
        ->call('markOne', $id)
        ->assertSet('visuallyUnreadIds', []);

    expect($this->user->notifications()->first()->read_at)->not->toBeNull();
});

it('marks all as read', function () {
    $this->user->notify(new BellTestNotification);
    $this->user->notify(new BellTestNotification);

    Livewire::test('synotif::bell')->call('markAll');

    expect($this->user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('dispatches a notify browser event only for notifications created after mount, flagged toastr', function () {
    $component = Livewire::test('synotif::bell');

    $this->user->notify(new BellTestNotification(toastr: true));
    $this->user->notify(new BellTestNotification(toastr: false));

    $component->call('refresh')
        ->assertDispatched('notify');
});

it('puts the Livewire root on the polling wrapper so the dropdown and poll belong to the component', function () {
    // A template that opens with @if gets Livewire's block-marker comment on the
    // same line as its first tag, so the root attributes landed on the inner
    // <button> and left wire:poll / wire:click outside the component.
    $html = Livewire::test('synotif::bell')->html();

    preg_match('/<([a-z]+)\b[^>]*\bwire:id="/i', $html, $root);
    preg_match('/<[a-z]+\b[^>]*\bwire:id="[^"]*"[^>]*>/i', $html, $rootTag);

    expect($root[1] ?? null)->toBe('div')
        ->and($rootTag[0] ?? '')->toContain('wire:poll.15s="refresh"');
});

it('still renders a single root element when notifications are disabled', function () {
    config(['synapps.apps.notifications.enabled' => false]);

    $html = Livewire::test('synotif::bell')->html();

    expect($html)->toMatch('/<div\b[^>]*\bwire:id="/')
        ->and($html)->not->toContain('synotif-bell-trigger');
});
