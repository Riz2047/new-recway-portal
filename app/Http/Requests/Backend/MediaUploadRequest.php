<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use App\Support\Helper\MediaHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('media.create');
    }

    public function rules(): array
    {
        $limits = MediaHelper::getUploadLimits();
        $maxFileSizeKb = floor($limits['effective_max_filesize'] / 1024); // Convert to KB for Laravel validation

        $rules = [
            'files' => 'required|array|max:' . $limits['max_file_uploads'],
            'files.*' => [
                'required',
                'file',
                'max:' . $maxFileSizeKb, // in KB
            ],
        ];

        // Add MIME type restrictions for demo mode
        if (config('app.demo_mode', false)) {
            $allowedMimeTypes = implode(',', MediaHelper::getAllowedMimeTypesForDemo());
            $rules['files.*'][] = 'mimetypes:' . $allowedMimeTypes;
        }

        return $rules;
    }

    public function messages(): array
    {
        $limits = MediaHelper::getUploadLimits();

        $messages = [
            'files.required' => __('Please select at least one file to upload.'),
            'files.max' => __('You can upload a maximum of :max files at once.', ['max' => $limits['max_file_uploads']]),
            'files.*.required' => __('Each file is required.'),
            'files.*.file' => __('Each upload must be a valid file.'),
            'files.*.max' => __('Each file cannot exceed :max. Current PHP limit: :limit', [
                'max' => $limits['effective_max_filesize_formatted'],
                'limit' => $limits['effective_max_filesize_formatted'],
            ]),
        ];

        // Add demo mode specific message
        if (config('app.demo_mode', false)) {
            $messages['files.*.mimetypes'] = __('In demo mode, only images, videos, PDFs, and documents (Word, Excel, PowerPoint, text files) are allowed.');
        }

        return $messages;
    }

    protected function prepareForValidation(): void
    {
        // Check for PHP upload errors before Laravel validation
        $phpError = MediaHelper::checkPhpUploadError();
        if ($phpError) {
            // Add the error to the validator
            $this->getValidatorInstance()->after(function ($validator) use ($phpError) {
                $validator->errors()->add('php_upload_limit', $phpError['message']);
            });
        }
    }
}
