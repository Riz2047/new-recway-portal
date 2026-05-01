<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\Facades\Hook;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class UserService
{
    public function getUsers(array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = User::applyFilters($filters);

        return $query->paginateData([
            'per_page' => $filters['per_page'] ?? config('settings.default_pagination') ?? 10,
        ]);
    }

    public function createUser(array $data): User
    {
        $userData = $this->prepareUserData($data, true);
        $user = User::create($userData);

        $this->syncUserRoles($user, $data);

        return $user;
    }

    public function getUserById(int $id): User
    {
        return User::findOrFail($id);
    }

    public function updateUser(User $user, array $data): User
    {
        $updateData = $this->prepareUserData($data, false);
        $user->update($updateData);

        $this->syncUserRoles($user, $data);

        return $user->refresh();
    }

    public function createUserWithRelations(array $data): User
    {
        $user = $this->createUser($data);
        return $user->load('roles');
    }

    public function updateUserWithRelations(User $user, array $data): User
    {
        $updatedUser = $this->updateUser($user, $data);
        return $updatedUser->load('roles');
    }

    public function createUserWithMetadata(array $data, $request = null): User
    {
        return DB::transaction(function () use ($data, $request) {
            $userData = $this->prepareUserDataWithAvatar($data, true);
            $user = new User($userData);

            $user = $this->applyFilters($user, $request, 'user_store_before_save');
            $user->save();
            $user = $this->applyFilters($user, $request, 'user_store_after_save');

            $this->handleUserMetadata($user, $data, 'create', $request);
            $this->handleUserRoles($user, $data);

            return $user;
        });
    }

    public function updateUserWithMetadata(User $user, array $data, $request = null): User
    {
        return DB::transaction(function () use ($user, $data, $request) {
            $userData = $this->prepareUserDataWithAvatar($data, false, $user);
            $this->updateUserAttributes($user, $userData);

            $user = $this->applyFilters($user, $request, 'user_update_before_save');
            $user->save();
            $user = $this->applyFilters($user, $request, 'user_update_after_save');

            $this->handleUserMetadata($user, $data, 'update', $request);
            $this->handleUserRoles($user, $data, 'update');

            return $user;
        });
    }

    private function prepareUserData(array $data, bool $isCreate = true): array
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'username' => $data['name'] ?? null,
        ];

        if ($isCreate || $this->shouldUpdatePassword($data)) {
            $userData['password'] = Hash::make($data['password']);
        }

        return $userData;
    }

    private function prepareUserDataWithAvatar(array $data, bool $isCreate = true, ?User $existingUser = null): array
    {
        $userData = $this->prepareUserData($data, $isCreate);
        $userData['avatar_id'] = $data['avatar_id'] ?? ($existingUser?->avatar_id);

        return $userData;
    }

    private function shouldUpdatePassword(array $data): bool
    {
        return isset($data['password']) && ! empty($data['password']);
    }

    private function updateUserAttributes(User $user, array $userData): void
    {
        foreach ($userData as $key => $value) {
            if ($value !== null) {
                $user->$key = $value;
            }
        }
    }

    private function applyFilters(User $user, $request, string $hookName): User
    {
        return Hook::applyFilters($hookName, $user, $request) ?: $user;
    }

    private function syncUserRoles(User $user, array $data): void
    {
        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }
    }

    private function getUserMetadataFieldGroups(): array
    {
        return [
            'profile' => ['display_name', 'bio', 'timezone', 'locale', 'phone', 'parent_staff_members', 'can_upload_report'],
            'social' => ['social_facebook', 'social_x', 'social_youtube', 'social_linkedin', 'social_website'],
        ];
    }

    private function shouldProcessMetadataField(string $field, array $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }

        // For checkbox fields like can_upload_report, always save if key exists
        if ($field === 'can_upload_report') {
            return true;
        }

        // For array fields like parent_staff_members, check if array is not empty
        if ($field === 'parent_staff_members' && is_array($data[$field])) {
            return !empty(array_filter($data[$field]));
        }

        // For phone, save if key exists (even if empty string, to allow clearing)
        if ($field === 'phone') {
            return true;
        }

        return !empty($data[$field]);
    }

    private function getMetadataFieldValueForUpdate(string $field, array $data, $request = null): ?string
    {
        // Priority: request object, then validated data
        if ($request && $request->has($field)) {
            $value = $request->input($field, '');
            // Handle checkbox fields (can_upload_report)
            if ($field === 'can_upload_report') {
                return $value ? '1' : '0';
            }
            // Handle array fields (e.g., parent_staff_members)
            if ($field === 'parent_staff_members' && is_array($value)) {
                return implode(',', array_filter($value));
            }
            return is_array($value) ? '' : $value;
        }

        if (! $request && array_key_exists($field, $data)) {
            $value = $data[$field] ?? '';
            // Handle checkbox fields (can_upload_report)
            if ($field === 'can_upload_report') {
                return $value ? '1' : '0';
            }
            // Handle array fields
            if ($field === 'parent_staff_members' && is_array($value)) {
                return implode(',', array_filter($value));
            }
            return is_array($value) ? '' : $value;
        }

        return null; // Field not provided
    }

    private function handleUserRoles(User $user, array $data, string $operation = 'create'): void
    {
        if (! isset($data['roles'])) {
            return;
        }

        match ($operation) {
            'create' => $this->assignUserRoles($user, $data['roles']),
            'update' => $this->updateUserRoles($user, $data['roles']),
            default => throw new InvalidArgumentException("Unsupported operation: {$operation}")
        };
    }

    private function assignUserRoles(User $user, array $roles): void
    {
        if (! $roles) {
            return;
        }

        $filteredRoles = array_filter($roles);

        if (! empty($filteredRoles)) {
            $user->syncRoles($filteredRoles);
        }
    }

    private function updateUserRoles(User $user, array $roles): void
    {
        $user->roles()->detach();
        $this->assignUserRoles($user, $roles);
    }

    private function handleUserMetadata(User $user, array $data, string $operation = 'create', $request = null): void
    {
        $allFields = collect($this->getUserMetadataFieldGroups())->flatten();

        $metadataToProcess = $allFields
            ->map(fn ($field) => $this->prepareMetadataRecord($user, $field, $data, $operation, $request))
            ->filter()
            ->values();

        if ($metadataToProcess->isEmpty()) {
            return;
        }

        match ($operation) {
            'create' => $this->bulkCreateMetadata($user, $metadataToProcess),
            'update' => $this->bulkUpdateMetadata($user, $metadataToProcess),
            default => throw new InvalidArgumentException("Unsupported operation: {$operation}")
        };
    }

    private function prepareMetadataRecord(User $user, string $field, array $data, string $operation, $request = null): ?array
    {
        if ($operation === 'create' && ! $this->shouldProcessMetadataField($field, $data)) {
            return null;
        }

        if ($operation === 'update') {
            $value = $this->getMetadataFieldValueForUpdate($field, $data, $request);
            if ($value === null) {
                return null;
            }
            $data[$field] = $value;
        }

        // Handle special fields that come as arrays (e.g., parent_staff_members)
        $metaValue = $data[$field];
        if ($field === 'parent_staff_members' && is_array($metaValue)) {
            // Convert array to comma-separated string, filter out empty values
            $metaValue = implode(',', array_filter($metaValue));
        }
        // Handle checkbox fields (can_upload_report)
        if ($field === 'can_upload_report') {
            // Convert to string: '1' if checked, '0' if unchecked
            $metaValue = ($metaValue === '1' || $metaValue === 1 || $metaValue === true) ? '1' : '0';
        }

        return [
            'user_id' => $user->id,
            'meta_key' => $field,
            'meta_value' => $metaValue,
            'type' => 'string',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function bulkCreateMetadata(User $user, Collection $metadataRecords): void
    {
        $user->userMeta()->insert($metadataRecords->toArray());
    }

    private function bulkUpdateMetadata(User $user, Collection $metadataRecords): void
    {
        $user->userMeta()->upsert(
            $metadataRecords->toArray(),
            ['user_id', 'meta_key'], // Unique columns
            ['meta_value', 'type', 'updated_at'] // Columns to update
        );
    }

    /**
     * Bulk delete users by IDs, skipping admin and current user.
     * Returns the number of users deleted.
     */
    public function bulkDeleteUsers(array $ids, ?int $currentUserId = null): int
    {
        $users = User::whereIn('id', $ids)->get();
        $deletedCount = 0;

        foreach ($users as $user) {
            if ($user->hasRole('Admin')) {
                continue;
            }
            if ($currentUserId && $user->id == $currentUserId) {
                continue;
            }
            $user->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }
}
