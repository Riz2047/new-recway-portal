<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'email_template.view');
    }

    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        return $this->checkPermission($user, 'email_template.view');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'email_template.create');
    }

    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        return $this->checkPermission($user, 'email_template.edit')
            || $this->checkPermission($user, 'email_template.update');
    }

    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        return $this->checkPermission($user, 'email_template.delete');
    }
}
