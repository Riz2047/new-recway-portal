<?php

declare(strict_types=1);

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

pest()->use(RefreshDatabase::class);

beforeEach(function () {
    // Disable CSRF protection for tests.
    $this->withoutMiddleware(VerifyCsrfToken::class);

    // Create admin user with permissions
    $this->admin = User::factory()->create();
    $adminRole = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);

    // Create necessary permissions
    Permission::create(['name' => 'user.view']);
    Permission::create(['name' => 'user.create']);
    Permission::create(['name' => 'user.edit']);
    Permission::create(['name' => 'user.delete']);

    $adminRole->syncPermissions([
        'user.view',
        'user.create',
        'user.edit',
        'user.delete',
    ]);

    $this->admin->assignRole($adminRole);
});

test('admin can view users list', function () {
    $response = $this->actingAs($this->admin)->get('/admin/users');
    $response->assertStatus(200);
    $response->assertViewIs('backend.pages.users.index');
});

test('admin can create user', function () {
    $role = Role::create(['name' => 'editor']);

    $response = $this->actingAs($this->admin)->post('/admin/users', [
        'name' => 'John',
        'email' => 'john@example.com',
        'username' => 'johndoe',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => ['editor'],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'name' => 'John',
        'email' => 'john@example.com',
        'username' => 'johndoe',
    ]);

    $user = User::where('email', 'john@example.com')->first();
    expect($user->hasRole('editor'))->toBeTrue();
});

test('admin can update user', function () {
    $user = User::create([
        'name' => 'Original',
        'email' => 'original@example.com',
        'username' => 'originaluser',
        'password' => Hash::make('password'),
    ]);

    $role = Role::create(['name' => 'editor']);

    $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
        'name' => 'Updated',
        'email' => 'updated@example.com',
        'username' => 'updateduser',
        'roles' => ['editor'],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated',
        'email' => 'updated@example.com',
        'username' => 'updateduser',
    ]);

    $updatedUser = User::find($user->id);
    expect($updatedUser->hasRole('editor'))->toBeTrue();
});

test('admin can delete user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->admin)->delete("/admin/users/{$user->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admin cannot delete themselves', function () {
    $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->id}");

    $response->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

test('user without permission cannot manage users', function () {
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get('/admin/users');
    $response->assertStatus(403);

    $response = $this->actingAs($regularUser)->post('/admin/users', [
        'name' => 'New',
        'username' => 'newuser',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    $response->assertStatus(403);
});

test('validation works when creating user', function () {
    $response = $this->actingAs($this->admin)->post('/admin/users', [
        'name' => '',
        'email' => '',
        'password' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
});
