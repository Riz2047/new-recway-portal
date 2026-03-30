<?php

declare(strict_types=1);

namespace App\Support;

final class EmailTemplateVariable
{
    /**
     * Derive a stable variable key from display title: trim, collapse whitespace to single underscores.
     */
    public static function fromTitle(string $title): string
    {
        $trimmed = trim($title);
        $withUnderscores = preg_replace('/\s+/u', '_', $trimmed) ?? $trimmed;

        return $withUnderscores === '' ? '_' : $withUnderscores;
    }
}
