<?php

declare(strict_types=1);

use App\Models\Candidate;
use App\Models\CandidateHistory;
use App\Models\CompanyManager;
use App\Models\Customer;
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

    // Statuses involved in the customer "Change Status" decision flow.
    // IDs are preserved from the old system and are hardcoded in the controller,
    // so they must be inserted explicitly (Status::create() ignores 'id' since
    // it is not mass-assignable).
    foreach ([
        ['id' => 6, 'variable' => 'investigation_spo', 'status' => 'Under investigation'],
        ['id' => 47, 'variable' => 'Interviewcompletedwithout_deviation', 'status' => 'Interview completed without deviations'],
        ['id' => 4, 'variable' => 'approved', 'status' => 'Approved'],
        ['id' => 7, 'variable' => 'denied', 'status' => 'Denied'],
        ['id' => 39, 'variable' => 'follow_up_under_investigation', 'status' => 'Under investigation'],
        ['id' => 37, 'variable' => 'Approved_followup', 'status' => 'Approved'],
        ['id' => 42, 'variable' => 'Denied_followup', 'status' => 'Denied'],
    ] as $row) {
        DB::table('statuses')->insert($row + [
            'status_type' => $category->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
});

function makeOrderCandidate(int $status): Candidate
{
    return Candidate::create([
        'name' => 'John',
        'surname' => 'Doe',
        'email' => 'john@example.com',
        'order_id' => 'ORD-' . $status,
        'cus_id' => test()->customer->id,
        'interview_id' => test()->serviceType->id,
        'status' => $status,
    ]);
}

it('hides the change status action when the customer is not a company manager', function (): void {
    $candidate = makeOrderCandidate(6);

    $response = $this->actingAs($this->customerUser)
        ->get(route('customer.orders.show', $candidate->id));

    $response->assertOk();
    $response->assertDontSee('Change Status');
});

it('hides the change status action when the order is not awaiting a decision', function (): void {
    CompanyManager::create(['cus_id' => $this->customer->id, 'company' => 'Acme Corp']);
    $candidate = makeOrderCandidate(4);

    $response = $this->actingAs($this->customerUser)
        ->get(route('customer.orders.show', $candidate->id));

    $response->assertOk();
    $response->assertDontSee('Change Status');
});

it('shows approved/denied options for a company manager when the order is awaiting a decision', function (): void {
    CompanyManager::create(['cus_id' => $this->customer->id, 'company' => 'Acme Corp']);
    $candidate = makeOrderCandidate(6);

    $response = $this->actingAs($this->customerUser)
        ->get(route('customer.orders.show', $candidate->id));

    $response->assertOk();
    $response->assertSee('Change Status');

    $content = $response->getContent();
    expect($content)->toContain('value="4"');
    expect($content)->toContain('value="7"');
    expect($content)->not->toContain('value="37"');
    expect($content)->not->toContain('value="42"');
});

it('shows follow-up approved/denied options when the order is in follow-up review', function (): void {
    CompanyManager::create(['cus_id' => $this->customer->id, 'company' => 'Acme Corp']);
    $candidate = makeOrderCandidate(39);

    $response = $this->actingAs($this->customerUser)
        ->get(route('customer.orders.show', $candidate->id));

    $response->assertOk();
    $response->assertSee('Change Status');

    $content = $response->getContent();
    expect($content)->toContain('value="37"');
    expect($content)->toContain('value="42"');
    expect($content)->not->toContain('value="4"');
    expect($content)->not->toContain('value="7"');
});

it('updates the order status and logs history when a company manager submits a valid status', function (): void {
    CompanyManager::create(['cus_id' => $this->customer->id, 'company' => 'Acme Corp']);
    $candidate = makeOrderCandidate(6);

    $response = $this->actingAs($this->customerUser)
        ->post(route('customer.orders.change-status', $candidate->id), [
            'status' => 4,
            'comment' => 'Looks good',
        ]);

    $response->assertRedirect(route('customer.orders.show', $candidate->id));
    $response->assertSessionHas('success');

    expect((int) $candidate->fresh()->status)->toBe(4);
    expect(CandidateHistory::where('order_id', $candidate->id)->exists())->toBeTrue();
});

it('rejects a status that is not offered for the order current status', function (): void {
    CompanyManager::create(['cus_id' => $this->customer->id, 'company' => 'Acme Corp']);
    $candidate = makeOrderCandidate(6);

    $response = $this->actingAs($this->customerUser)
        ->post(route('customer.orders.change-status', $candidate->id), [
            'status' => 37,
        ]);

    $response->assertSessionHas('error');
    expect((int) $candidate->fresh()->status)->toBe(6);
});

it('rejects a status change when the customer is not a company manager', function (): void {
    $candidate = makeOrderCandidate(6);

    $response = $this->actingAs($this->customerUser)
        ->post(route('customer.orders.change-status', $candidate->id), [
            'status' => 4,
        ]);

    $response->assertSessionHas('error');
    expect((int) $candidate->fresh()->status)->toBe(6);
});
