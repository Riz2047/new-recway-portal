<?php

declare(strict_types=1);

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Role::query()->firstOrCreate(
        ['name' => 'Admin', 'guard_name' => 'web'],
        ['name' => 'Admin', 'guard_name' => 'web'],
    );
});

it('creates an email template with derived variable', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $response = $this->actingAs($user)->post(route('admin.email-templates.store'), [
        'title' => 'Welcome Email',
        'body' => '<p>Hello</p>',
    ]);

    $response->assertRedirect(route('admin.email-templates.index'));

    $template = EmailTemplate::query()->first();
    expect($template)->not->toBeNull()
        ->and($template->title)->toBe('Welcome Email')
        ->and($template->variable)->toBe('Welcome_Email')
        ->and($template->body)->toContain('Hello');
});

it('rejects duplicate variable derived from title', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    EmailTemplate::query()->create([
        'title' => 'Welcome Email',
        'variable' => 'Welcome_Email',
        'body' => null,
    ]);

    $response = $this->actingAs($user)->post(route('admin.email-templates.store'), [
        'title' => 'Welcome Email',
        'body' => null,
    ]);

    $response->assertSessionHasErrors('title');
    expect(EmailTemplate::query()->count())->toBe(1);
});

it('forbids index without permission', function () {
    Role::query()->firstOrCreate(
        ['name' => 'User', 'guard_name' => 'web'],
        ['name' => 'User', 'guard_name' => 'web'],
    );

    $user = User::factory()->create();
    $user->assignRole('User');

    $this->actingAs($user)->get(route('admin.email-templates.index'))->assertForbidden();
});
