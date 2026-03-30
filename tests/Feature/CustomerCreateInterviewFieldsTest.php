<?php

declare(strict_types=1);

use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

pest()->use(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'Customer', 'guard_name' => 'web']);
});

it('persists interview_upload_allowed and combine_interview_service when creating a customer', function () {
    $category = ServiceCategory::query()->create([
        'name' => 'Interviews',
        'name_sv' => 'Intervjuer',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => $category->id,
        'name' => 'Standard interview',
        'price' => 0,
    ]);

    $customer = app(CustomerService::class)->createCustomer([
        'name' => 'Test User',
        'email' => 'customer-fields-test@example.com',
        'password' => 'password1',
        'company' => 'Acme AB',
        'org_no' => '556677-8899',
        'interview_upload_allowed' => true,
        'combine_interview_service' => (string) $service->id,
        'combine_bk_and_security' => [],
        'combine_status' => [],
        'services' => [],
        'statuses' => [],
    ]);

    $customer->refresh();

    expect($customer->interview_upload_allowed)->toBeTrue();
    expect($customer->combine_interview_service)->toBe((string) $service->id);
});

it('stores combine_bk_and_security service ids as comma-separated string', function () {
    $bkCategory = ServiceCategory::query()->create([
        'name' => 'Background Check',
        'name_sv' => 'Bakgrundskontroll',
    ]);

    $serviceA = ServiceType::query()->create([
        'service_category_id' => $bkCategory->id,
        'name' => 'BK A',
        'price' => 0,
    ]);

    $serviceB = ServiceType::query()->create([
        'service_category_id' => $bkCategory->id,
        'name' => 'BK B',
        'price' => 0,
    ]);

    $customer = app(CustomerService::class)->createCustomer([
        'name' => 'Test User 2',
        'email' => 'customer-combine-test@example.com',
        'password' => 'password1',
        'company' => 'Beta AB',
        'org_no' => '112233-4455',
        'interview_upload_allowed' => false,
        'combine_bk_and_security' => [(string) $serviceA->id, (string) $serviceB->id],
        'combine_status' => [],
        'services' => [],
        'statuses' => [],
    ]);

    $customer->refresh();

    expect($customer->combine_bk_and_security)->toBe($serviceA->id . ',' . $serviceB->id);
});
