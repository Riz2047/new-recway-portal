<?php

declare(strict_types=1);

namespace App\Http\Requests\ServiceCategory;

use App\Http\Requests\FormRequest;

class UpdateServiceCategoryRequest extends FormRequest
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
        $route = $this->route();
        $serviceCategoryId = $route->parameter('service_category') 
            ?? $route->parameter('id');

        // If serviceCategoryId is a model instance, get its ID
        if (is_object($serviceCategoryId) && method_exists($serviceCategoryId, 'getKey')) {
            $serviceCategoryId = $serviceCategoryId->getKey();
        }

        // Ensure serviceCategoryId is an integer or null
        $serviceCategoryId = $serviceCategoryId ? (int) $serviceCategoryId : null;

        return [
            'name' => 'required|string|max:255|unique:service_categories,name,' . ($serviceCategoryId ?? 'NULL'),
            'name_sv' => 'nullable|string|max:255',
        ];
    }
}
