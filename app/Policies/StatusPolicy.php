<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Status;
use App\Models\User;

class StatusPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'status.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Status $status): bool
    {
        return $this->checkPermission($user, 'status.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'status.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Status $status): bool
    {
        return $this->checkPermission($user, 'status.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Status $status): bool
    {
        return $this->checkPermission($user, 'status.delete');
    }
}
