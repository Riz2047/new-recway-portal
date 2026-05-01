<?php

declare(strict_types=1);

use App\Livewire\Customer\Tabs\FormBuilder;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

pest()->use(RefreshDatabase::class);

it('adds a custom field and saves form builder data', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'Acme AB',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => ServiceCategory::query()->create([
            'name' => 'Checks',
            'name_sv' => 'Kontroller',
        ])->id,
        'name' => 'Background Check',
        'price' => 0,
    ]);

    DB::table('service_type_user')->insert([
        'service_type_id' => $service->id,
        'cus_id' => $customer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(FormBuilder::class, ['customerId' => $customer->id])
        ->set('selectedService', $service->id)
        ->set('newField.label', 'Passport Number')
        ->set('newField.type', 'text')
        ->set('newField.placeholder', 'Enter Passport Number')
        ->set('newField.required', true)
        ->call('addCustomField', 'personal_info')
        ->call('saveFormBuilder');

    $row = DB::table('form_builders')
        ->where('cus_id', $customer->id)
        ->where('servicetype_id', $service->id)
        ->first();

    expect($row)->not->toBeNull();

    $decoded = json_decode((string) $row->form, true);
    $personalInfo = $decoded['form_builder']['personal_info'] ?? [];

    expect(array_keys($personalInfo))->toContain('text,Passport Number,passport_number,Enter Passport Number,required,,new_field,');
});

it('adds note button field into billing info section', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'Blue AB',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => ServiceCategory::query()->create([
            'name' => 'Interviews',
            'name_sv' => 'Intervjuer',
        ])->id,
        'name' => 'Interview Service',
        'price' => 0,
    ]);

    DB::table('service_type_user')->insert([
        'service_type_id' => $service->id,
        'cus_id' => $customer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(FormBuilder::class, ['customerId' => $customer->id])
        ->set('selectedService', $service->id)
        ->call('addDefaultField', 'note')
        ->call('saveFormBuilder');

    $row = DB::table('form_builders')
        ->where('cus_id', $customer->id)
        ->where('servicetype_id', $service->id)
        ->first();

    $decoded = json_decode((string) $row->form, true);
    $billingInfo = $decoded['form_builder']['billing_info'] ?? [];

    expect(array_keys($billingInfo))->toContain('text,Note,note,Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.,,,new_field,');
});

it('loads saved form builder fields for selected customer service', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'Legacy AB',
    ]);

    $category = ServiceCategory::query()->create([
        'name' => 'Verification',
        'name_sv' => 'Verifiering',
    ]);

    $serviceA = ServiceType::query()->create([
        'service_category_id' => $category->id,
        'name' => 'A Service',
        'price' => 0,
    ]);

    $serviceB = ServiceType::query()->create([
        'service_category_id' => $category->id,
        'name' => 'B Service',
        'price' => 0,
    ]);

    DB::table('service_type_user')->insert([
        ['service_type_id' => $serviceA->id, 'cus_id' => $customer->id, 'created_at' => now(), 'updated_at' => now()],
        ['service_type_id' => $serviceB->id, 'cus_id' => $customer->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('form_builders')->insert([
        'cus_id' => $customer->id,
        'servicetype_id' => $serviceB->id,
        // Simulate legacy double-encoded payload shape.
        'form' => json_encode(json_encode([
            'form_builder' => [
                'personal_info' => [
                    'text,Employee ID,employee_id,Enter employee id,required,,new_field,' => 'Enter employee id',
                ],
                'billing_info' => [
                    'text,Reference,reference,Enter reference,,,new_field,' => 'Enter reference',
                ],
            ],
        ])),
        'created_at' => now(),
        'updated_at' => now()->addMinute(),
    ]);

    Livewire::test(FormBuilder::class, ['customerId' => $customer->id])
        ->assertSet('selectedService', $serviceB->id)
        ->assertSee('Employee ID')
        ->assertSee('Reference');
});

it('keeps sections empty when no form exists for selected customer service', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'No Form AB',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => ServiceCategory::query()->create([
            'name' => 'No Form Category',
            'name_sv' => 'Ingen mall',
        ])->id,
        'name' => 'No Form Service',
        'price' => 0,
    ]);

    DB::table('service_type_user')->insert([
        'service_type_id' => $service->id,
        'cus_id' => $customer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(FormBuilder::class, ['customerId' => $customer->id])
        ->assertSet('selectedService', $service->id)
        ->assertSet('formSections.personal_info', [])
        ->assertSet('formSections.billing_info', [])
        ->assertSee('No form fields found for this customer and service type. Add form fields and save.');
});

it('can move fields up and down to adjust order', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'Order AB',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => ServiceCategory::query()->create([
            'name' => 'Ordering',
            'name_sv' => 'Ordning',
        ])->id,
        'name' => 'Ordering Service',
        'price' => 0,
    ]);

    DB::table('service_type_user')->insert([
        'service_type_id' => $service->id,
        'cus_id' => $customer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(FormBuilder::class, ['customerId' => $customer->id])
        ->set('selectedService', $service->id)
        ->set('newField.label', 'First Field')
        ->call('addCustomField', 'personal_info')
        ->set('newField.label', 'Second Field')
        ->call('addCustomField', 'personal_info')
        ->assertSet('formSections.personal_info.0.name', 'first_field')
        ->assertSet('formSections.personal_info.1.name', 'second_field')
        ->call('moveFieldDown', 'personal_info', 0)
        ->assertSet('formSections.personal_info.0.name', 'second_field')
        ->assertSet('formSections.personal_info.1.name', 'first_field')
        ->call('moveFieldUp', 'personal_info', 1)
        ->assertSet('formSections.personal_info.0.name', 'first_field');
});

it('can move a field directly to another position', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'Drag AB',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => ServiceCategory::query()->create([
            'name' => 'Drag',
            'name_sv' => 'Dra',
        ])->id,
        'name' => 'Drag Service',
        'price' => 0,
    ]);

    DB::table('service_type_user')->insert([
        'service_type_id' => $service->id,
        'cus_id' => $customer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(FormBuilder::class, ['customerId' => $customer->id])
        ->set('selectedService', $service->id)
        ->set('newField.label', 'First')
        ->call('addCustomField', 'billing_info')
        ->set('newField.label', 'Second')
        ->call('addCustomField', 'billing_info')
        ->set('newField.label', 'Third')
        ->call('addCustomField', 'billing_info')
        ->assertSet('formSections.billing_info.0.name', 'first')
        ->assertSet('formSections.billing_info.1.name', 'second')
        ->assertSet('formSections.billing_info.2.name', 'third')
        ->call('moveFieldTo', 'billing_info', 0, 2)
        ->assertSet('formSections.billing_info.0.name', 'second')
        ->assertSet('formSections.billing_info.1.name', 'third')
        ->assertSet('formSections.billing_info.2.name', 'first');
});

it('keeps copy customer names visible after selection', function () {
    $ownerOne = User::factory()->create(['name' => 'Alpha User']);
    $ownerTwo = User::factory()->create(['name' => 'Beta User']);

    $customer = Customer::query()->create([
        'user_id' => $ownerOne->id,
        'company' => 'Main Customer',
    ]);

    $otherCustomer = Customer::query()->create([
        'user_id' => $ownerTwo->id,
        'company' => 'Other Customer',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => ServiceCategory::query()->create([
            'name' => 'Copy',
            'name_sv' => 'Kopiera',
        ])->id,
        'name' => 'Copy Service',
        'price' => 0,
    ]);

    DB::table('service_type_user')->insert([
        'service_type_id' => $service->id,
        'cus_id' => $customer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(FormBuilder::class, ['customerId' => $customer->id])
        ->assertSee('Alpha User')
        ->assertSee('Beta User')
        ->set('copyCustomer', (string) $otherCustomer->id)
        ->assertSet('copyCustomer', $otherCustomer->id)
        ->assertSee('Alpha User')
        ->assertSee('Beta User');
});

it('auto loads fields when copy customer and service are selected', function () {
    $mainOwner = User::factory()->create(['name' => 'Main User']);
    $sourceOwner = User::factory()->create(['name' => 'Source User']);

    $mainCustomer = Customer::query()->create([
        'user_id' => $mainOwner->id,
        'company' => 'Main Co',
    ]);

    $sourceCustomer = Customer::query()->create([
        'user_id' => $sourceOwner->id,
        'company' => 'Source Co',
    ]);

    $category = ServiceCategory::query()->create([
        'name' => 'Auto Copy',
        'name_sv' => 'Auto Kopiera',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => $category->id,
        'name' => 'Auto Copy Service',
        'price' => 0,
    ]);

    DB::table('service_type_user')->insert([
        ['service_type_id' => $service->id, 'cus_id' => $mainCustomer->id, 'created_at' => now(), 'updated_at' => now()],
        ['service_type_id' => $service->id, 'cus_id' => $sourceCustomer->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('form_builders')->insert([
        'cus_id' => $sourceCustomer->id,
        'servicetype_id' => $service->id,
        'form' => json_encode([
            'form_builder' => [
                'personal_info' => [
                    'text,Source Field,source_field,Enter source,required,,new_field,' => 'Enter source',
                ],
                'billing_info' => [],
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(FormBuilder::class, ['customerId' => $mainCustomer->id])
        ->set('copyCustomer', (string) $sourceCustomer->id)
        ->set('copyService', (string) $service->id)
        ->assertSet('formSections.personal_info.0.name', 'source_field')
        ->assertSee('Source Field');
});
