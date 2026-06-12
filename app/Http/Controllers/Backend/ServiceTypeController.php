<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ServiceTypeController extends Controller
{
    /**
     * List service types for a category.
     */
    public function index(int $categoryId): JsonResponse
    {
        $serviceTypes = ServiceType::with('customers')
            ->where('service_category_id', $categoryId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $serviceTypes,
        ]);
    }

    /**
     * Store a new service type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'place' => 'sometimes|boolean',
            'country' => 'sometimes|boolean',
            'delivery_days' => 'nullable|integer|min:0',
            'customers' => 'nullable|array',
            'customers.*' => 'exists:customers,id',
        ]);

        try {
            DB::beginTransaction();

            $serviceType = ServiceType::create([
                'service_category_id' => $validated['service_category_id'],
                'name' => $validated['name'],
                'price' => $validated['price'],
                'description' => $validated['description'],
                'place' => $validated['place'] ?? false,
                'country' => $validated['country'] ?? false,
                'delivery_days' => $validated['delivery_days'] ?? null,
            ]);

            if (! empty($validated['customers'])) {
                $serviceType->customers()->sync($this->withChildCustomers($validated['customers']));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Service type created successfully.'),
                'data' => $serviceType->load('customers'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('Failed to create service type.') . ' ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getCustomers(): JsonResponse
    {
        // Only list parent customers (no parent_id); their child customers
        // are assigned automatically when the parent is selected.
        $customers = Customer::join('users', 'customers.user_id', '=', 'users.id')
        ->whereNull('customers.parent_id')
        ->select('customers.id as id', 'users.name', 'users.email')
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->name . ' (' . $item->email . ')',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Update the specified service type.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'place' => 'sometimes|boolean',
            'country' => 'sometimes|boolean',
            'delivery_days' => 'nullable|integer|min:0',
            'customers' => 'nullable|array',
            'customers.*' => 'exists:customers,id',
        ]);

        try {
            DB::beginTransaction();

            $serviceType = ServiceType::findOrFail($id);
            $serviceType->update([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'description' => $validated['description'],
                'place' => $validated['place'] ?? false,
                'country' => $validated['country'] ?? false,
                'delivery_days' => $validated['delivery_days'] ?? null,
            ]);

            if (isset($validated['customers'])) {
                $serviceType->customers()->sync($this->withChildCustomers($validated['customers']));
            } else {
                $serviceType->customers()->detach();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Service type updated successfully.'),
                'data' => $serviceType->load('customers'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('Failed to update service type.') . ' ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add the child customers of any selected parent customers to the list.
     *
     * @param  array<int, int|string>  $customerIds
     * @return array<int, int|string>
     */
    private function withChildCustomers(array $customerIds): array
    {
        $childIds = Customer::whereIn('parent_id', $customerIds)->pluck('id')->all();

        return array_values(array_unique([...$customerIds, ...$childIds]));
    }

    /**
     * Remove the specified service type.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $serviceType = ServiceType::findOrFail($id);
            $serviceType->delete();

            return response()->json([
                'success' => true,
                'message' => __('Service type deleted successfully.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete service type.'),
            ], 500);
        }
    }
}
