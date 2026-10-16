<?php

use App\Enums\Role;
use App\Models\User;

test('register page shows the CineBook design', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Your seat in the story')
        ->assertSee('Create account')
        ->assertSee('data-test="register-user-button"', false);
});

// A dataset runs each role as its own test with a fresh session
// (the register route is guest-only, so one test cannot register twice).
test('the register form cannot be used to create a privileged account', function (string $role) {
    $this->post(route('register.store'), [
        'name' => 'Sneaky User',
        'email' => 'sneaky@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => $role,
    ]);

    $user = User::where('email', 'sneaky@example.com')->firstOrFail();

    expect($user->role)->toBe(Role::Customer)
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->isStaff())->toBeFalse();
})->with(['admin', 'staff']);

test('a mismatched password confirmation is rejected with an error', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'something-else',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
});

test('a duplicate email is rejected with an error', function () {
    $existing = User::factory()->create();

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Someone Else',
            'email' => $existing->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('email');
});
