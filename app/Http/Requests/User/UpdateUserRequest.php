<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\Hooks\UserFilterHook;
use App\Http\Requests\FormRequest;
use App\Support\Facades\Hook;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the controller using policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get user ID from the request parameters
        // Try different route parameter names for different controllers (staff, admin, user, id)
        $route = $this->route();
        $userId = $route->parameter('staff') 
            ?? $route->parameter('admin')
            ?? $route->parameter('user') 
            ?? $route->parameter('id');

        // If userId is a model instance, get its ID
        if (is_object($userId) && method_exists($userId, 'getKey')) {
            $userId = $userId->getKey();
        }

        // Ensure userId is an integer or null
        $userId = $userId ? (int) $userId : null;

        return Hook::applyFilters(UserFilterHook::USER_UPDATE_VALIDATION_RULES, [
            /** @example "Jane" */
            'first_name' => 'required|max:50',

            /** @example "Smith" */
            'last_name' => 'required|max:50',

            /** @example "jane.smith@example.com" */
            'email' => 'required|max:100|email|unique:users,email,' . ($userId ?? 'NULL'),

            /** @example "janesmith456" */
            'username' => 'required|max:100|unique:users,username,' . ($userId ?? 'NULL'),

            /** @example "newPassword789" */
            'password' => 'nullable|min:6|confirmed',

            /** @example "123" */
            'avatar_id' => 'nullable|exists:media,id',

            /** @example [1, 2, 3] */
            'roles' => 'nullable|array',
            'roles.*' => 'nullable|exists:roles,name',
        ], $userId);
    }
}
