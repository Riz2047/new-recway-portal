<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'candidate.view_all')
            || $this->checkPermission($user, 'candidate.view_own');
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return $this->checkPermission($user, 'candidate.view_all')
            || $this->checkPermission($user, 'candidate.view_own');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'candidate.create');
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return $this->checkPermission($user, 'candidate.update');
    }

    public function delete(User $user, Candidate $candidate): bool
    {
        return $this->checkPermission($user, 'candidate.delete');
    }

    public function bulkDelete(User $user): bool
    {
        return $this->checkPermission($user, 'candidate.delete');
    }
}
