<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'service-category.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceCategory $serviceCategory): bool
    {
        return $this->checkPermission($user, 'service-category.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'service-category.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceCategory $serviceCategory): bool
    {
        return $this->checkPermission($user, 'service-category.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceCategory $serviceCategory): bool
    {
        return $this->checkPermission($user, 'service-category.delete');
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function bulkDelete(User $user): bool
    {
        return $this->checkPermission($user, 'service-category.delete');
    }
}
