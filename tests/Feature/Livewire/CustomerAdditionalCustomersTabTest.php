<?php

declare(strict_types=1);

use App\Livewire\Customer\Tabs\AdditionalCustomers;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

pest()->use(RefreshDatabase::class);

it('adds a new additional customer for the selected customer', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'Acme AB',
    ]);

    Livewire::test(AdditionalCustomers::class, ['customerId' => $customer->id])
        ->call('showAddForm')
        ->set('name', 'Muhammad Rizwan')
        ->set('email', 'rizwanramzan648@gmail.com')
        ->call('saveAdditionalCustomer')
        ->assertSet('showForm', false);

    expect(DB::table('additional_customers')
        ->where('cus_id', $customer->id)
        ->where('name', 'Muhammad Rizwan')
        ->where('email', 'rizwanramzan648@gmail.com')
        ->exists())->toBeTrue();
});

it('updates and deletes an additional customer', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'Blue AB',
    ]);

    $additionalCustomerId = DB::table('additional_customers')->insertGetId([
        'cus_id' => $customer->id,
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(AdditionalCustomers::class, ['customerId' => $customer->id])
        ->call('editAdditionalCustomer', $additionalCustomerId)
        ->set('name', 'New Name')
        ->set('email', 'new@example.com')
        ->call('saveAdditionalCustomer');

    expect(DB::table('additional_customers')
        ->where('id', $additionalCustomerId)
        ->value('name'))->toBe('New Name');

    Livewire::test(AdditionalCustomers::class, ['customerId' => $customer->id])
        ->call('deleteAdditionalCustomer', $additionalCustomerId);

    expect(DB::table('additional_customers')
        ->where('id', $additionalCustomerId)
        ->exists())->toBeFalse();
});
