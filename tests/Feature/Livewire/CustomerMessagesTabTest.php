<?php

declare(strict_types=1);

use App\Livewire\Customer\Tabs\Messages;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

pest()->use(RefreshDatabase::class);

it('loads message columns and current values for selected customer service', function () {
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

    $status = Status::query()->create([
        'variable' => 'pending',
        'status' => 'Pending',
        'status_type' => 1,
    ]);

    DB::table('status_services')->insert([
        'status_id' => $status->id,
        'service_id' => $service->id,
        'msg_col' => 'pending_msg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('messages')->insert([
        'cus_id' => $customer->id,
        'interview_id' => $service->id,
        'pending_msg' => 'Pending template text',
    ]);

    Livewire::test(Messages::class, ['customerId' => $customer->id])
        ->set('selectedService', $service->id)
        ->call('loadData')
        ->assertSet('columns', ['pending_msg'])
        ->assertSet('messageValues.pending_msg', 'Pending template text');
});

it('copies source customer service messages into current customer selected service', function () {
    $targetUser = User::factory()->create();
    $targetCustomer = Customer::query()->create([
        'user_id' => $targetUser->id,
        'company' => 'Target AB',
    ]);

    $sourceUser = User::factory()->create();
    $sourceCustomer = Customer::query()->create([
        'user_id' => $sourceUser->id,
        'company' => 'Source AB',
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
        [
            'service_type_id' => $service->id,
            'cus_id' => $targetCustomer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'service_type_id' => $service->id,
            'cus_id' => $sourceCustomer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $status = Status::query()->create([
        'variable' => 'approved',
        'status' => 'Approved',
        'status_type' => 1,
    ]);

    DB::table('status_services')->insert([
        'status_id' => $status->id,
        'service_id' => $service->id,
        'msg_col' => 'approved_msg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('messages')->insert([
        'cus_id' => $sourceCustomer->id,
        'interview_id' => $service->id,
        'approved_msg' => 'Source approved template',
    ]);

    Livewire::test(Messages::class, ['customerId' => $targetCustomer->id])
        ->set('selectedService', $service->id)
        ->set('copyCustomer', $sourceCustomer->id)
        ->set('copyService', $service->id)
        ->call('copyMessages')
        ->assertSet('messageValues.approved_msg', 'Source approved template');

    expect(DB::table('messages')
        ->where('cus_id', $targetCustomer->id)
        ->where('interview_id', $service->id)
        ->value('approved_msg'))->toBe('Source approved template');
});
