<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Place;
use Illuminate\Database\Eloquent\Collection;

class PlaceService
{
    /**
     * Get all places
     */
    public function getAll(): Collection
    {
        return Place::orderBy('name')->get();
    }

    /**
     * Find place by ID
     */
    public function findById(int $id): ?Place
    {
        return Place::find($id);
    }

    /**
     * Create a new place
     */
    public function create(array $data): Place
    {
        return Place::create([
            'name' => $data['name'],
        ]);
    }

    /**
     * Update a place
     */
    public function update(Place $place, array $data): Place
    {
        $place->update([
            'name' => $data['name'],
        ]);

        return $place->fresh();
    }

    /**
     * Delete a place
     */
    public function delete(Place $place): bool
    {
        return $place->delete();
    }
}

