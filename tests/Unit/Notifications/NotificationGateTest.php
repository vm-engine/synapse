<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use VmEngine\Synapse\Notifications\NotificationGate;

afterEach(function () {
    Schema::dropIfExists('notifications');
});

it('is disabled when the config flag is off even if the table exists', function () {
    Schema::create('notifications', fn ($t) => $t->uuid('id')->primary());
    config(['synapps.apps.notifications.enabled' => false]);

    expect(NotificationGate::enabled())->toBeFalse();
});

it('is disabled when the config flag is on but the table is missing', function () {
    Schema::dropIfExists('notifications');
    config(['synapps.apps.notifications.enabled' => true]);

    expect(NotificationGate::enabled())->toBeFalse();
});

it('is enabled only when both the flag is on and the table exists', function () {
    Schema::create('notifications', fn ($t) => $t->uuid('id')->primary());
    config(['synapps.apps.notifications.enabled' => true]);

    expect(NotificationGate::enabled())->toBeTrue();
});
