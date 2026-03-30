<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

class EmailTemplateDatatable extends Datatable
{
    public string $model = EmailTemplate::class;
    public array $disabledRoutes = ['view'];
    public string $newResourceLinkPermission = 'email_template.create';
    public string $newResourceLinkLabel = '';
    public string $newResourceLinkIcon = 'lucide:mail-plus';
    public bool $enableCheckbox = false;
    public string $routePrefix = 'admin';

    public function mount(?string $routePrefix = null): void
    {
        parent::mount();
        $this->setActionLabels();
        $this->newResourceLinkLabel = __('New Email Template');
        $this->routePrefix = in_array($routePrefix, ['admin', 'staff'], true)
            ? $routePrefix
            : (request()->routeIs('staff.*') ? 'staff' : 'admin');
    }

    public function getSearchbarPlaceholder(): string
    {
        return __('Search by title or variable...');
    }

    protected function getHeaders(): array
    {
        return [
            [
                'id' => 'title',
                'title' => __('Title'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'title',
            ],
            [
                'id' => 'variable',
                'title' => __('Variable'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'variable',
            ],
            [
                'id' => 'updated_at',
                'title' => __('Updated At'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'updated_at',
            ],
            [
                'id' => 'actions',
                'title' => __('Actions'),
                'width' => null,
                'sortable' => false,
                'is_action' => true,
                'align' => 'right',
            ],
        ];
    }

    protected function buildQuery(): QueryBuilder
    {
        $query = QueryBuilder::for($this->model)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('variable', 'like', "%{$this->search}%");
                });
            });

        return $this->sortQuery($query);
    }

    public function renderVariableColumn(EmailTemplate $emailTemplate): string
    {
        return '<code class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-1 text-xs">' . e($emailTemplate->variable) . '</code>';
    }

    public function renderActionsColumn($emailTemplate): string
    {
        $buttons = '<div class="flex items-center justify-end gap-2">';

        if (Gate::allows('update', $emailTemplate)) {
            $buttons .= '<a href="' . e($this->getEditRouteUrl($emailTemplate)) . '" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="' . e(__('Edit')) . '">'
                . '<iconify-icon icon="lucide:pencil" class="text-lg"></iconify-icon>'
                . '</a>';
        }

        if (Gate::allows('delete', $emailTemplate)) {
            $buttons .= '<button type="button" wire:click="deleteItem(' . $emailTemplate->id . ')" wire:loading.attr="disabled" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="' . e(__('Delete')) . '">'
                . '<iconify-icon icon="lucide:trash-2" class="text-lg"></iconify-icon>'
                . '</button>';
        }

        $buttons .= '</div>';

        return $buttons;
    }

    public function handleRowDelete(Model|EmailTemplate $emailTemplate): bool
    {
        $this->authorize('delete', $emailTemplate);
        $emailTemplate->delete();

        return true;
    }

    protected function getPermissions(): array
    {
        return [
            'view' => 'email_template.view',
            'create' => 'email_template.create',
            'edit' => 'email_template.edit',
            'delete' => 'email_template.delete',
        ];
    }

    public function getRoutes(): array
    {
        return [
            'create' => $this->routePrefix . '.email-templates.create',
            'edit' => $this->routePrefix . '.email-templates.edit',
            'delete' => $this->routePrefix . '.email-templates.destroy',
        ];
    }

    protected function getItemRouteParameters($item): array
    {
        return ['email_template' => $item->id];
    }
}
