<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Collection;

class ServiceCategoryService
{
    /**
     * Get all service categories
     */
    public function getAll(): Collection
    {
        return ServiceCategory::orderBy('name')->get();
    }

    /**
     * Find service category by ID
     */
    public function findById(int $id): ?ServiceCategory
    {
        return ServiceCategory::find($id);
    }

    /**
     * Create a new service category
     */
    public function create(array $data): ServiceCategory
    {
        return ServiceCategory::create([
            'name' => $data['name'],
            'name_sv' => $data['name_sv'] ?? null,
        ]);
    }

    /**
     * Update a service category
     */
    public function update(ServiceCategory $serviceCategory, array $data): ServiceCategory
    {
        $serviceCategory->update([
            'name' => $data['name'],
            'name_sv' => $data['name_sv'] ?? null,
        ]);

        return $serviceCategory->fresh();
    }

    /**
     * Delete a service category
     */
    public function delete(ServiceCategory $serviceCategory): bool
    {
        return $serviceCategory->delete();
    }
}

