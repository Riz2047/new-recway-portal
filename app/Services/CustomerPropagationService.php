<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Propagates parent customer data (messages, forms) to direct child customers
 * for the same service type, whenever the parent is updated.
 *
 * Only children that already have the service type assigned are affected.
 * Never touches children of other parents or unrelated services.
 */
class CustomerPropagationService
{
    /**
     * After a parent customer's message templates are saved for a service,
     * push the same templates to every direct child that has that service.
     *
     * @param array<string, mixed> $templates  The full templates array to propagate.
     */
    public function propagateMessages(int $parentCusId, int $serviceId, array $templates): void
    {
        $childIds = $this->childIdsWithService($parentCusId, $serviceId);

        if ($childIds->isEmpty()) {
            return;
        }

        $json = json_encode($templates, JSON_UNESCAPED_UNICODE);

        foreach ($childIds as $childId) {
            DB::table('messages')->updateOrInsert(
                ['cus_id' => $childId, 'interview_id' => $serviceId],
                ['templates' => $json]
            );
        }
    }

    /**
     * After a parent customer's form builder is saved for a service,
     * push the same form JSON to every direct child that has that service.
     *
     * @param string $formJson  The raw JSON string stored in form_builders.form.
     */
    public function propagateForms(int $parentCusId, int $serviceId, string $formJson): void
    {
        $childIds = $this->childIdsWithService($parentCusId, $serviceId);

        if ($childIds->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($childIds as $childId) {
            DB::table('form_builders')->updateOrInsert(
                ['cus_id' => $childId, 'servicetype_id' => $serviceId],
                ['form' => $formJson, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    /**
     * Quick check — skip all propagation work if the customer has no children.
     */
    public function hasChildren(int $cusId): bool
    {
        return Customer::where('parent_id', $cusId)->exists();
    }

    /**
     * Returns IDs of direct children of $parentCusId that have $serviceId assigned.
     *
     * @return Collection<int, int>
     */
    private function childIdsWithService(int $parentCusId, int $serviceId): Collection
    {
        $allChildIds = Customer::where('parent_id', $parentCusId)->pluck('id');

        if ($allChildIds->isEmpty()) {
            return collect();
        }

        return DB::table('service_type_user')
            ->whereIn('cus_id', $allChildIds)
            ->where('service_type_id', $serviceId)
            ->pluck('cus_id');
    }
}
