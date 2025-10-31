<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SynApps\Modules\Example\Models\Example;

uses(RefreshDatabase::class);

it('can create an example', function () {
    $example = Example::factory()->create([
        'text' => 'Test Text',
        'email' => 'test@example.com',
    ]);

    expect($example)
        ->text->toBe('Test Text')
        ->email->toBe('test@example.com');

    $this->assertDatabaseHas('examples', [
        'text' => 'Test Text',
        'email' => 'test@example.com',
    ]);
});

it('renders the example form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('backend.example.form'));

    $response->assertStatus(200);
});

it('renders the example list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('backend.example.index'));

    $response->assertStatus(200);
});
