<?php

declare(strict_types=1);

namespace App\Support\Modules;

use Illuminate\Support\Str;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Laravel\LaravelFileRepository;

class CustomFileRepository extends LaravelFileRepository
{
    /**
     * Get a module path for a specific module with kebab-case folder names.
     */
    public function getModulePath($module): string
    {
        try {
            return $this->findOrFail($module)->getPath() . '/';
        } catch (ModuleNotFoundException $e) {
            // Use a kebab-case for folder names instead of studly case
            return $this->getPath() . '/' . Str::kebab($module) . '/';
        }
    }
}
