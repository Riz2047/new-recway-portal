<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('user can view login form', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
    $response->assertViewIs('backend.auth.login');
});

test('user can login with correct credentials', function () {
    $user = User::factory()->create([
        'email' => 'superadmin@example.com',
        'password' => bcrypt('12345678'),
    ]);

    $response = $this->post('/admin/login', [
        'email' => 'superadmin@example.com',
        'password' => '12345678',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});

test('user cannot login with incorrect password', function () {
    $response = $this->from('/admin/login')
        ->post('/admin/login', [
            'email' => 'superadmin@example.com',
            'password' => 'wrong-password',
        ]);

    $response->assertRedirect('/admin/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('user cannot login with email that does not exist', function () {
    $response = $this->from('/admin/login')->post('/admin/login', [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/admin/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('remember me functionality works', function () {
    $user = User::factory()->create([
        'email' => 'superadmin@example.com',
        'password' => bcrypt('12345678'),
    ]);

    $response = $this->post('/admin/login', [
        'email' => 'superadmin@example.com',
        'password' => '12345678',
        'remember' => 'on',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);

    // Check for the remember cookie.
    $cookies = $response->headers->getCookies();
    $hasRememberCookie = false;

    foreach ($cookies as $cookie) {
        if (strpos($cookie->getName(), 'remember_web_') === 0) {
            $hasRememberCookie = true;
            break;
        }
    }

    expect($hasRememberCookie)->toBeTrue();
});
