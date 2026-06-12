<?php

declare(strict_types=1);

use App\Models\Candidate;
use App\Models\Customer;
use App\Models\Place;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Role::create(['name' => 'Customer', 'guard_name' => 'web']);

    $this->customerUser = User::factory()->create();
    $this->customerUser->assignRole('Customer');
    $this->customer = Customer::create(['user_id' => $this->customerUser->id]);

    $category = ServiceCategory::create(['name' => 'Interview', 'name_sv' => 'Intervju']);
    $this->serviceType = ServiceType::create([
        'service_category_id' => $category->id,
        'name' => 'Identity Verification',
        'price' => 100,
    ]);
});

it('hides empty fields on the order show page', function (): void {
    $candidate = Candidate::create([
        'name' => 'John',
        'surname' => 'Doe',
        'email' => 'john@example.com',
        'order_id' => 'ORD-EMPTY',
        'cus_id' => $this->customer->id,
        'interview_id' => $this->serviceType->id,
        'phone' => null,
        'place' => null,
        'country' => null,
        'staff_id' => null,
        'booked' => null,
        'delivery_date' => null,
        'referensperson' => null,
        'reference' => null,
        'comment' => null,
    ]);

    $response = $this->actingAs($this->customerUser)
        ->get(route('customer.orders.show', $candidate->id));

    $response->assertOk();
    $response->assertDontSee('Phone');
    $response->assertDontSee('Location');
    $response->assertDontSee('Country');
    $response->assertDontSee('Staff Assigned');
    $response->assertDontSee('Interview Date');
    $response->assertDontSee('Delivery Date');
    $response->assertSee('No billing details added.');
});

it('shows populated fields on the order show page', function (): void {
    $place = Place::create(['name' => 'Stockholm']);
    $staff = User::factory()->create(['name' => 'Jane Staff']);

    $candidate = Candidate::create([
        'name' => 'John',
        'surname' => 'Doe',
        'email' => 'john@example.com',
        'order_id' => 'ORD-FULL',
        'cus_id' => $this->customer->id,
        'interview_id' => $this->serviceType->id,
        'phone' => '0701234567',
        'place' => $place->id,
        'country' => 'Sweden',
        'staff_id' => $staff->id,
        'booked' => now(),
        'delivery_date' => now()->addDays(5),
        'referensperson' => 'Jane Invoice',
        'reference' => 'REF-123',
        'comment' => 'Please invoice quarterly',
    ]);

    $response = $this->actingAs($this->customerUser)
        ->get(route('customer.orders.show', $candidate->id));

    $response->assertOk();
    $response->assertSee('Phone');
    $response->assertSee('0701234567');
    $response->assertSee('Location');
    $response->assertSee('Stockholm');
    $response->assertSee('Country');
    $response->assertSee('Sweden');
    $response->assertSee('Staff Assigned');
    $response->assertSee('Jane Staff');
    $response->assertSee('Interview Date');
    $response->assertSee('Delivery Date');
    $response->assertSee('Invoice Recipient');
    $response->assertSee('Jane Invoice');
    $response->assertSee('Invoice Reference');
    $response->assertSee('REF-123');
    $response->assertSee('Comment');
    $response->assertSee('Please invoice quarterly');
});

it('uses the service form builder billing labels when available', function (): void {
    DB::table('form_builders')->insert([
        'cus_id' => $this->customer->id,
        'servicetype_id' => $this->serviceType->id,
        'form' => json_encode([
            'form_builder' => [
                'personal_info' => [
                    'text,Name,name,,required,,,' => '',
                ],
                'billing_info' => [
                    'text,Company Reference,pref,,,,,' => '',
                    'text,PO Number,ref,,,,,' => '',
                    'textarea,Special Instructions,comment,,,,,' => '',
                ],
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $candidate = Candidate::create([
        'name' => 'John',
        'surname' => 'Doe',
        'email' => 'john@example.com',
        'order_id' => 'ORD-FORM',
        'cus_id' => $this->customer->id,
        'interview_id' => $this->serviceType->id,
        'referensperson' => 'Jane Invoice',
        'reference' => 'REF-123',
        'comment' => 'Please invoice quarterly',
    ]);

    $response = $this->actingAs($this->customerUser)
        ->get(route('customer.orders.show', $candidate->id));

    $response->assertOk();
    $response->assertSee('Company Reference');
    $response->assertSee('PO Number');
    $response->assertSee('Special Instructions');
    $response->assertDontSee('Invoice Recipient');
    $response->assertDontSee('Invoice Reference');
});

it('matches billing labels by text when field names are slugified labels', function (): void {
    DB::table('form_builders')->insert([
        'cus_id' => $this->customer->id,
        'servicetype_id' => $this->serviceType->id,
        'form' => json_encode([
            'form_builder' => [
                'personal_info' => [
                    'text,Name,name,,required,,,' => '',
                ],
                'billing_info' => [
                    'text,Affärsområde*,affärsområde*,,required,,new_field,' => '',
                    'text,Ansvarig chef / Hiring manager name  ( Invoice Recipient )*,ansvarig_chef/_hiring_manager_name(_invoice_recipient)*,,required,,new_field,' => '',
                    'text,DO (5 siffror) *,do(5_siffror)*,,required,,new_field,' => '',
                    'textarea,Note,note,,,,,' => '',
                ],
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $candidate = Candidate::create([
        'name' => 'John',
        'surname' => 'Doe',
        'email' => 'john@example.com',
        'order_id' => 'ORD-SLUG',
        'cus_id' => $this->customer->id,
        'interview_id' => $this->serviceType->id,
        'referensperson' => 'test',
        'reference' => 'test',
        'comment' => null,
        'note' => 'testing',
    ]);

    $response = $this->actingAs($this->customerUser)
        ->get(route('customer.orders.show', $candidate->id));

    $response->assertOk();
    $response->assertSee('Ansvarig chef / Hiring manager name  ( Invoice Recipient )');
    $response->assertSee('DO (5 siffror)');
    $response->assertDontSee('Invoice Reference');
});

it('falls back to default billing labels when the form has no billing fields', function (): void {
    DB::table('form_builders')->insert([
        'cus_id' => $this->customer->id,
        'servicetype_id' => $this->serviceType->id,
        'form' => json_encode([
            'form_builder' => [
                'personal_info' => [
                    'text,Name,name,,required,,,' => '',
                ],
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $candidate = Candidate::create([
        'name' => 'John',
        'surname' => 'Doe',
        'email' => 'john@example.com',
        'order_id' => 'ORD-NOBILL',
        'cus_id' => $this->customer->id,
        'interview_id' => $this->serviceType->id,
        'referensperson' => 'Jane Invoice',
        'reference' => 'REF-123',
        'comment' => 'Please invoice quarterly',
    ]);

    $response = $this->actingAs($this->customerUser)
        ->get(route('customer.orders.show', $candidate->id));

    $response->assertOk();
    $response->assertSee('Invoice Recipient');
    $response->assertSee('Invoice Reference');
    $response->assertSee('Comment');
});
