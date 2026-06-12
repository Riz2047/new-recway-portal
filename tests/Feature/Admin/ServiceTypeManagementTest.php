<?php

declare(strict_types=1);

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Customer;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $this->admin = User::factory()->create();
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    $this->admin->assignRole($adminRole);

    $this->category = ServiceCategory::create([
        'name' => 'Interview',
        'name_sv' => 'Intervju',
    ]);
});

test('store service type persists place and country flags', function () {
    $response = $this->actingAs($this->admin)->postJson(route('admin.service-types.store'), [
        'service_category_id' => $this->category->id,
        'name' => 'Phone Interview',
        'price' => 100,
        'description' => 'Test description',
        'place' => true,
        'country' => true,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('service_types', [
        'service_category_id' => $this->category->id,
        'name' => 'Phone Interview',
        'place' => true,
        'country' => true,
    ]);
});

test('update service type toggles place and country flags', function () {
    $serviceType = ServiceType::create([
        'service_category_id' => $this->category->id,
        'name' => 'Phone Interview',
        'price' => 100,
        'place' => true,
        'country' => true,
    ]);

    $response = $this->actingAs($this->admin)->putJson(route('admin.service-types.update', $serviceType->id), [
        'name' => 'Phone Interview',
        'price' => 100,
        'description' => null,
        'place' => false,
        'country' => false,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('service_types', [
        'id' => $serviceType->id,
        'place' => false,
        'country' => false,
    ]);
});

test('store service type persists delivery days', function () {
    $response = $this->actingAs($this->admin)->postJson(route('admin.service-types.store'), [
        'service_category_id' => $this->category->id,
        'name' => 'Background Check',
        'price' => 50,
        'description' => 'Test description',
        'delivery_days' => 5,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('service_types', [
        'service_category_id' => $this->category->id,
        'name' => 'Background Check',
        'delivery_days' => 5,
    ]);
});

test('update service type changes delivery days', function () {
    $serviceType = ServiceType::create([
        'service_category_id' => $this->category->id,
        'name' => 'Background Check',
        'price' => 50,
        'delivery_days' => 3,
    ]);

    $response = $this->actingAs($this->admin)->putJson(route('admin.service-types.update', $serviceType->id), [
        'name' => 'Background Check',
        'price' => 50,
        'description' => null,
        'delivery_days' => 7,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('service_types', [
        'id' => $serviceType->id,
        'delivery_days' => 7,
    ]);
});

test('customers list only includes parent customers', function () {
    $parentCustomer = Customer::create(['user_id' => User::factory()->create()->id]);
    $childCustomer = Customer::create([
        'user_id' => User::factory()->create()->id,
        'parent_id' => $parentCustomer->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.service-types.customers'));

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($parentCustomer->id);
    expect($ids)->not->toContain($childCustomer->id);
});

test('assigning a service type to a parent customer also assigns its child customers', function () {
    $parentCustomer = Customer::create(['user_id' => User::factory()->create()->id]);
    $childCustomer = Customer::create([
        'user_id' => User::factory()->create()->id,
        'parent_id' => $parentCustomer->id,
    ]);

    $serviceType = ServiceType::create([
        'service_category_id' => $this->category->id,
        'name' => 'Phone Interview',
        'price' => 100,
    ]);

    $response = $this->actingAs($this->admin)->putJson(route('admin.service-types.update', $serviceType->id), [
        'name' => 'Phone Interview',
        'price' => 100,
        'description' => null,
        'customers' => [$parentCustomer->id],
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('service_type_user', [
        'service_type_id' => $serviceType->id,
        'cus_id' => $parentCustomer->id,
    ]);

    $this->assertDatabaseHas('service_type_user', [
        'service_type_id' => $serviceType->id,
        'cus_id' => $childCustomer->id,
    ]);
});
