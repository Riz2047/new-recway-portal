<?php

declare(strict_types=1);

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

pest()->use(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $this->admin = User::factory()->create();
    $adminRole = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);

    Permission::create(['name' => 'status.create']);
    Permission::create(['name' => 'status.view']);

    $adminRole->syncPermissions([
        'status.create',
        'status.view',
    ]);

    $this->admin->assignRole($adminRole);
});

test('store status persists selected services in status_services pivot', function () {
    $category = ServiceCategory::create([
        'name' => 'Category A',
        'name_sv' => 'Kategori A',
    ]);

    $serviceTypeA = ServiceType::create([
        'service_category_id' => $category->id,
        'name' => 'Service A',
        'price' => 100,
    ]);

    $serviceTypeB = ServiceType::create([
        'service_category_id' => $category->id,
        'name' => 'Service B',
        'price' => 200,
    ]);

    $response = $this->actingAs($this->admin)->post("/admin/service-category/{$category->id}/status", [
        'status' => 'Pending',
        'status_sv' => 'Vantar',
        'variable' => 'pending_status',
        'status_detail' => 'Pending details',
        'color' => '#51f467',
        'status_icon' => 'bi-check-circle',
        'services' => [(string) $serviceTypeA->id, (string) $serviceTypeB->id],
        'message' => 'Some message',
        'msg_col' => 'msg_pending',
    ]);

    $response->assertRedirect(route('admin.status.index', $category->id));

    $status = Status::where('variable', 'pending_status')->firstOrFail();

    $this->assertDatabaseHas('status_services', [
        'status_id' => $status->id,
        'service_id' => $serviceTypeA->id,
        'msg_col' => 'msg_pending',
    ]);

    $this->assertDatabaseHas('status_services', [
        'status_id' => $status->id,
        'service_id' => $serviceTypeB->id,
        'msg_col' => 'msg_pending',
    ]);
});
