<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Models\Candidate;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Spatie\QueryBuilder\QueryBuilder;

class CandidateDatatable extends Datatable
{
    public string $model = Candidate::class;
    public array $disabledRoutes = ['view', 'edit', 'delete'];
    public string $newResourceLinkPermission = 'customer.create';
    public string $newResourceLinkLabel = 'New Candidate';
    public string $panelPrefix = 'admin';

    protected function getHeaders(): array
    {
        return [
            ['id' => 'order_id', 'title' => __('Order ID'), 'sortable' => true, 'sortBy' => 'order_id'],
            ['id' => 'name', 'title' => __('Name'), 'sortable' => true, 'sortBy' => 'name'],
            ['id' => 'customer', 'title' => __('Customer'), 'sortable' => false],
            ['id' => 'service', 'title' => __('Service Type'), 'sortable' => false],
            ['id' => 'staff', 'title' => __('Staff'), 'sortable' => false],
            ['id' => 'status', 'title' => __('Status'), 'sortable' => false],
            ['id' => 'created_at', 'title' => __('Order Created'), 'sortable' => true, 'sortBy' => 'created_at'],
        ];
    }

    public function getSearchbarPlaceholder(): string
    {
        return __('Search by order id, name, email, or phone...');
    }

    protected function buildQuery(): QueryBuilder
    {
        $query = QueryBuilder::for(Candidate::query())
            ->with(['customer.user', 'serviceType', 'statusRelation', 'staff'])
            ->where(function ($builder): void {
                if (Schema::hasColumn('candidates', 'expired')) {
                    $builder->where('expired', 0);
                }
            });

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('order_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $this->sortQuery($query);
    }

    protected function getData(): CursorPaginator|LengthAwarePaginator|Paginator
    {
        if (! Schema::hasTable('candidates')) {
            $perPage = $this->perPage === __('All') ? 999999 : (int) $this->perPage;

            return new LengthAwarePaginator([], 0, $perPage, 1);
        }

        /** @var LengthAwarePaginator $data */
        $data = parent::getData();

        return $data;
    }

    public function renderNameColumn(Candidate $candidate): string
    {
        return '<span class="text-sm">' . e(trim($candidate->name . ' ' . $candidate->surname)) . '</span>';
    }

    public function renderCustomerColumn(Candidate $candidate): string
    {
        $name = $candidate->customer?->user?->name ?? '-';

        return '<span class="text-sm">' . e($name) . '</span>';
    }

    public function renderServiceColumn(Candidate $candidate): string
    {
        return '<span class="text-sm">' . e($candidate->serviceType?->name ?? '-') . '</span>';
    }

    public function renderStaffColumn(Candidate $candidate): string
    {
        return '<span class="text-sm">' . e($candidate->staff?->name ?? '-') . '</span>';
    }

    public function renderStatusColumn(Candidate $candidate): string
    {
        $label = $candidate->statusRelation?->status ?? '-';

        return '<span class="text-sm">' . e($label) . '</span>';
    }

    protected function getPermissions(): array
    {
        return [
            'view' => 'customer.view',
            'create' => 'customer.create',
            'edit' => 'customer.edit',
            'delete' => 'customer.delete',
        ];
    }

    public function getRoutes(): array
    {
        return [
            'create' => $this->panelPrefix . '.candidates.create',
        ];
    }

    protected function getNewResourceLinkRouteName(): string
    {
        return $this->panelPrefix . '.candidates.create';
    }
}
