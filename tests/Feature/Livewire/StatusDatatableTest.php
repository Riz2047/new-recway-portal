<?php

declare(strict_types=1);

use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\Datatable\StatusDatatable;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

pest()->use(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $this->admin = User::factory()->create();
    $adminRole = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);

    Permission::create(['name' => 'status.view']);
    Permission::create(['name' => 'status.create']);
    Permission::create(['name' => 'status.edit']);
    Permission::create(['name' => 'status.delete']);

    $adminRole->syncPermissions([
        'status.view',
        'status.create',
        'status.edit',
        'status.delete',
    ]);

    $this->admin->assignRole($adminRole);
});

it('renders status datatable scoped by service category', function () {
    $this->actingAs($this->admin);

    $categoryA = ServiceCategory::create(['name' => 'Cat A', 'name_sv' => 'Cat A']);
    $categoryB = ServiceCategory::create(['name' => 'Cat B', 'name_sv' => 'Cat B']);

    Status::create(['variable' => 'a1', 'status' => 'Alpha', 'status_type' => $categoryA->id]);
    Status::create(['variable' => 'b1', 'status' => 'Beta', 'status_type' => $categoryB->id]);

    Livewire::test(StatusDatatable::class, ['serviceCategoryId' => $categoryA->id])
        ->assertStatus(200)
        ->assertSee('Alpha')
        ->assertDontSee('Beta');
});

it('searches statuses by status, swedish status and variable', function () {
    $this->actingAs($this->admin);

    $category = ServiceCategory::create(['name' => 'Cat', 'name_sv' => 'Cat']);

    Status::create([
        'variable' => 'match_me',
        'status' => 'Matched Status',
        'status_sv' => 'Matchad',
        'status_type' => $category->id,
    ]);

    Status::create([
        'variable' => 'other_one',
        'status' => 'Other Status',
        'status_type' => $category->id,
    ]);

    Livewire::test(StatusDatatable::class, ['serviceCategoryId' => $category->id])
        ->set('search', 'match_me')
        ->assertSee('Matched Status')
        ->assertDontSee('Other Status')
        ->set('search', 'Matchad')
        ->assertSee('Matched Status')
        ->assertDontSee('Other Status');
});

it('paginates statuses', function () {
    $this->actingAs($this->admin);

    $category = ServiceCategory::create(['name' => 'Cat', 'name_sv' => 'Cat']);

    for ($i = 1; $i <= 15; $i++) {
        Status::create([
            'variable' => 'var_' . $i,
            'status' => 'Status ' . $i,
            'status_type' => $category->id,
        ]);
    }

    Livewire::test(StatusDatatable::class, ['serviceCategoryId' => $category->id])
        ->assertSee('Search by status, variable...')
        ->set('perPage', 10)
        ->set('page', 2)
        ->assertStatus(200);
});
