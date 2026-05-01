<?php

declare(strict_types=1);

namespace App\Http\Requests\EmailTemplate;

use App\Http\Requests\FormRequest;
use App\Models\EmailTemplate;
use App\Support\EmailTemplateVariable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|ValidationRule|Closure>>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }
                    $variable = EmailTemplateVariable::fromTitle($value);
                    if (EmailTemplate::query()->where('variable', $variable)->exists()) {
                        $fail(__('Another template already uses variable :var. Choose a different title.', ['var' => $variable]));
                    }
                },
            ],
            'body' => ['nullable', 'string'],
        ];
    }
}
