<?php

declare(strict_types=1);

use App\Livewire\Customer\Tabs\StatusManager;
use App\Models\CompanyManager;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

pest()->use(RefreshDatabase::class);

it('persists can_view_report when enabled', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'Acme AB',
    ]);

    Livewire::test(StatusManager::class, ['customerId' => $customer->id])
        ->set('selectedCompany', 'Acme AB')
        ->set('canViewReport', true)
        ->call('update')
        ->assertHasNoErrors();

    expect(
        CompanyManager::query()
            ->where('cus_id', $customer->id)
            ->value('can_view_report')
    )->toBeTrue();
});

it('persists can_view_report when disabled', function () {
    $user = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $user->id,
        'company' => 'Beta AB',
    ]);

    CompanyManager::query()->create([
        'cus_id' => $customer->id,
        'company' => 'Beta AB',
        'can_view_report' => true,
    ]);

    Livewire::test(StatusManager::class, ['customerId' => $customer->id])
        ->set('selectedCompany', 'Beta AB')
        ->set('canViewReport', false)
        ->call('update')
        ->assertHasNoErrors();

    expect(
        CompanyManager::query()
            ->where('cus_id', $customer->id)
            ->value('can_view_report')
    )->toBeFalse();
});
