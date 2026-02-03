<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

trait HandlesMediaOperations
{
    /**
     * Format file size from bytes to human readable format
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file type category based on mime type
     */
    protected function getFileTypeCategory(string $mimeType): string
    {
        $categories = [
            'image' => ['image/'],
            'video' => ['video/'],
            'audio' => ['audio/'],
            'document' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument',
                'text/',
            ],
            'archive' => [
                'application/zip',
                'application/x-rar',
                'application/x-7z',
            ],
        ];

        foreach ($categories as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_starts_with($mimeType, $pattern)) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    /**
     * Get media icon based on mime type
     */
    protected function getMediaIcon(string $mimeType): string
    {
        $category = $this->getFileTypeCategory($mimeType);

        $icons = [
            'image' => 'fa-image',
            'video' => 'fa-video',
            'audio' => 'fa-music',
            'document' => 'fa-file-text',
            'archive' => 'fa-file-archive',
            'other' => 'fa-file',
        ];

        return $icons[$category] ?? $icons['other'];
    }

    /**
     * Sanitize filename for safe storage
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);

        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Prevent double extensions
        $filename = preg_replace('/\.+/', '.', $filename);

        // Ensure filename is not too long
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 255 - strlen($extension) - 1) . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Check if file is dangerous based on extension and filename patterns
     */
    protected function isDangerousFile(UploadedFile $file): bool
    {
        $dangerousExtensions = [
            'php', 'php3', 'php4', 'php5', 'phtml', 'phps',
            'asp', 'aspx', 'jsp', 'jspx',
            'exe', 'com', 'bat', 'cmd', 'scr',
            'vbs', 'vbe', 'js', 'jar',
            'pl', 'py', 'rb', 'sh',
        ];

        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, $dangerousExtensions)) {
            return true;
        }

        // Check for double extensions
        $filename = $file->getClientOriginalName();
        if (preg_match('/\.(php|asp|jsp|exe|com|bat|cmd|scr|vbs|vbe|js|jar|pl|py|rb|sh)\./i', $filename)) {
            return true;
        }

        return false;
    }

    /**
     * Validate file headers match expected mime types
     */
    protected function validateFileHeaders(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        $validMimeTypes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'pdf' => ['application/pdf'],
            'mp4' => ['video/mp4'],
            'avi' => ['video/avi', 'video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav', 'audio/x-wav'],
            'ogg' => ['audio/ogg'],
        ];

        if (! isset($validMimeTypes[$extension])) {
            return true; // Allow unknown extensions
        }

        return in_array($mimeType, $validMimeTypes[$extension]);
    }

    /**
     * Generate a unique, secure filename
     */
    protected function generateUniqueFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = pathinfo($originalName, PATHINFO_FILENAME);

        $sanitizedName = $this->sanitizeFilename($name);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);

        return "{$sanitizedName}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Check if uploaded file passes security checks
     */
    protected function isSecureFile(UploadedFile $file): bool
    {
        if ($this->isDangerousFile($file)) {
            return false;
        }

        if (! $this->validateFileHeaders($file)) {
            return false;
        }

        return true;
    }
}
