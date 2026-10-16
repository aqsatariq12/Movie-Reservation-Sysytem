<?php

use App\Models\User;

test('login page shows the CineBook design', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Welcome back')
        ->assertSee('Forgot password?')
        ->assertSee('data-test="login-button"', false);
});

test('a wrong password sends the visitor back to login with an error', function () {
    $user = User::factory()->create();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a visitor sent here from a showtime sees the showtime notice', function () {
    $this->withSession(['url.intended' => url('/showtimes/1/seats')])
        ->get(route('login'))
        ->assertOk()
        ->assertSee('Your showtime is waiting')
        ->assertSee('Sign in &amp; return to showtime', false);
});

test('a visitor sent here from another page does not see the showtime notice', function () {
    $this->withSession(['url.intended' => url('/dashboard')])
        ->get(route('login'))
        ->assertOk()
        ->assertDontSee('Your showtime is waiting');
});
