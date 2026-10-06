<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use VmEngine\Synapse\Notifications\NotificationSetup;

it('reports auth as not installed when the synapps-auth Role class does not exist', function () {
    expect(NotificationSetup::authInstalled())->toBeFalse();
});

it('reports the notifications table as not ready when the table is missing', function () {
    Schema::dropIfExists('notifications');

    expect(NotificationSetup::tableReady())->toBeFalse();
});

it('reports the notifications table as ready once created', function () {
    Schema::create('notifications', function ($table) {
        $table->uuid('id')->primary();
        $table->string('type');
        $table->morphs('notifiable');
        $table->text('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });

    expect(NotificationSetup::tableReady())->toBeTrue();

    Schema::dropIfExists('notifications');
});

it('publishes the notifications:table migration only when no matching migration file exists', function () {
    $migrationsPath = database_path('migrations');
    File::ensureDirectoryExists($migrationsPath);
    $matches = glob($migrationsPath.'/*_create_notifications_table.php');
    foreach ($matches as $match) {
        File::delete($match);
    }

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('call')->once()->with('notifications:table');

    NotificationSetup::ensureMigration($command);
});

it('skips publishing when a notifications table migration file already exists', function () {
    $migrationsPath = database_path('migrations');
    File::ensureDirectoryExists($migrationsPath);
    $fixture = $migrationsPath.'/2020_01_01_000000_create_notifications_table.php';
    File::put($fixture, '<?php');

    $command = Mockery::mock(Command::class);
    $command->shouldNotReceive('call');

    NotificationSetup::ensureMigration($command);

    File::delete($fixture);
});
