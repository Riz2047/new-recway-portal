<?php

declare(strict_types=1);

namespace App\Http\Requests\Place;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlaceRequest extends FormRequest
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
        $placeId = $route->parameter('place') 
            ?? $route->parameter('id');

        // If placeId is a model instance, get its ID
        if (is_object($placeId) && method_exists($placeId, 'getKey')) {
            $placeId = $placeId->getKey();
        }

        // Ensure placeId is an integer or null
        $placeId = $placeId ? (int) $placeId : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('places', 'name')->ignore($placeId),
            ],
        ];
    }
}

