<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('roles', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });

    Schema::create('model_has_roles', function ($table) {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
    });
});

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('logout revokes every database session for the authenticated user', function () {
    config()->set('session.driver', 'database');

    $user = User::factory()->create();

    DB::table('sessions')->insert([
        [
            'id' => 'another-active-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'guest-session',
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ],
    ]);

    $this->actingAs($user)->post('/logout')->assertRedirect('/');

    $this->assertGuest();
    $this->assertDatabaseMissing('sessions', ['id' => 'another-active-session']);
    $this->assertDatabaseHas('sessions', ['id' => 'guest-session']);
    $this->get('/dashboard')->assertRedirect('/login');
});