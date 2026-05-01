<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);

    Permission::create(['name' => 'customer.view']);
    Permission::create(['name' => 'customer.create']);

    $adminRole->syncPermissions(['customer.view', 'customer.create']);
    $this->admin->assignRole($adminRole);
});

it('allows admin to open candidates index', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.candidates.index'))
        ->assertOk();
});

it('allows admin to open create candidate page', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.candidates.create'))
        ->assertOk()
        ->assertSee('Add Candidate');
});

it('loads customer specific services for candidate create form', function (): void {
    $customerUser = User::factory()->create(['name' => 'Acme Customer']);
    $customer = Customer::query()->create(['user_id' => $customerUser->id]);
    $category = ServiceCategory::query()->create(['name' => 'Background Check']);
    $firstService = ServiceType::query()->create([
        'service_category_id' => $category->id,
        'name' => 'Service A',
        'place' => '1',
        'country' => '0',
    ]);
    $secondService = ServiceType::query()->create([
        'service_category_id' => $category->id,
        'name' => 'Service B',
        'place' => '0',
        'country' => '1',
    ]);

    $customer->serviceTypes()->attach([$firstService->id, $secondService->id]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.candidates.services', ['cus_id' => $customer->id]))
        ->assertOk()
        ->assertJsonPath('services.0.id', $firstService->id)
        ->assertJsonPath('services.1.id', $secondService->id)
        ->assertJsonPath('services.0.place', 1)
        ->assertJsonPath('services.0.country', 0)
        ->assertJsonPath('services.1.place', 0)
        ->assertJsonPath('services.1.country', 1)
        ->assertJsonPath('selected_service_id', $firstService->id);
});

it('loads form builder by customer and service', function (): void {
    $customerUser = User::factory()->create(['name' => 'Builder Customer']);
    $customer = Customer::query()->create(['user_id' => $customerUser->id]);
    $category = ServiceCategory::query()->create(['name' => 'Screening']);
    $service = ServiceType::query()->create([
        'service_category_id' => $category->id,
        'name' => 'Interview Service',
    ]);

    $customer->serviceTypes()->attach([$service->id]);

    DB::table('form_builders')->insert([
        'cus_id' => $customer->id,
        'servicetype_id' => $service->id,
        'form' => json_encode([
            'form_builder' => [
                'personal_info' => [
                    'text,Name,name,,required,,,' => '',
                ],
                'billing_info' => [
                    'textarea,Note,note,,,,,' => '',
                ],
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.candidates.form', [
            'cus_id' => $customer->id,
            'interview_id' => $service->id,
        ]))
        ->assertOk()
        ->assertJsonPath('selected_service_id', $service->id)
        ->assertJsonPath('form_fields.0.name', 'name')
        ->assertJsonPath('form_fields.1.name', 'note');
});
