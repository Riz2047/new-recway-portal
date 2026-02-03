<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Place;
use App\Models\User;

class PlacePolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'place.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Place $place): bool
    {
        return $this->checkPermission($user, 'place.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'place.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Place $place): bool
    {
        return $this->checkPermission($user, 'place.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Place $place): bool
    {
        return $this->checkPermission($user, 'place.delete');
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function bulkDelete(User $user): bool
    {
        return $this->checkPermission($user, 'place.delete');
    }
}

