<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Models\Candidate;
use App\Models\ServiceType;
use App\Models\Status;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Spatie\QueryBuilder\QueryBuilder;

class CandidateDatatable extends Datatable
{
    public string $model = Candidate::class;
    public array $disabledRoutes = ['view', 'delete'];
    public string $newResourceLinkPermission = 'customer.create';
    public string $newResourceLinkLabel = 'New Candidate';
    public string $panelPrefix = 'admin';

    // Filters
    public string $statusFilter = '';
    public string $serviceFilter = '';
    public string $invoiceFilter = '';   // '' = all | '0' = not sent | '1' = sent

    public array $queryString = [
        ...parent::QUERY_STRING_DEFAULTS,
        'statusFilter' => ['except' => ''],
        'serviceFilter' => ['except' => ''],
        'invoiceFilter' => ['except' => ''],
    ];

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatingServiceFilter(): void
    {
        $this->resetPage();
    }
    public function updatingInvoiceFilter(): void
    {
        $this->resetPage();
    }

    protected function getHeaders(): array
    {
        return [
            ['id' => 'order_id',   'title' => __('Order ID'),      'sortable' => true,  'sortBy' => 'order_id'],
            ['id' => 'name',       'title' => __('Name'),           'sortable' => true,  'sortBy' => 'name'],
            ['id' => 'customer',   'title' => __('Customer'),       'sortable' => false],
            ['id' => 'service',    'title' => __('Service Type'),   'sortable' => false],
            ['id' => 'staff',      'title' => __('Staff'),          'sortable' => false],
            ['id' => 'status',     'title' => __('Status'),         'sortable' => false],
            ['id' => 'booked',     'title' => __('Interview Date'), 'sortable' => true,  'sortBy' => 'booked'],
            ['id' => 'created_at', 'title' => __('Created'),        'sortable' => true,  'sortBy' => 'created_at'],
            ['id' => 'actions',    'title' => __('Actions'),        'sortable' => false, 'is_action' => true],
        ];
    }

    public function getSearchbarPlaceholder(): string
    {
        return __('Search by order id, name, email, or phone...');
    }

    protected function getFilters(): array
    {
        $statusOptions = [];
        $serviceOptions = [];

        if (Schema::hasTable('statuses')) {
            Status::orderBy('status')->get(['id', 'status'])
                ->each(function ($s) use (&$statusOptions): void {
                    $statusOptions[$s->id] = $s->status;
                });
        }

        if (Schema::hasTable('service_types')) {
            ServiceType::orderBy('name')->get(['id', 'name'])
                ->each(function ($s) use (&$serviceOptions): void {
                    $serviceOptions[$s->id] = $s->name;
                });
        }

        return [
            [
                'id' => 'statusFilter',
                'label' => __('Status'),
                'allLabel' => __('All Statuses'),
                'filterLabel' => $this->statusFilter
                    ? (Status::find($this->statusFilter)?->status ?? __('Status'))
                    : __('Status'),
                'options' => $statusOptions,
                'selected' => $this->statusFilter,
                'route' => '',
            ],
            [
                'id' => 'serviceFilter',
                'label' => __('Service'),
                'allLabel' => __('All Services'),
                'filterLabel' => $this->serviceFilter
                    ? (ServiceType::find($this->serviceFilter)?->name ?? __('Service'))
                    : __('Service'),
                'options' => $serviceOptions,
                'selected' => $this->serviceFilter,
                'route' => '',
            ],
            [
                'id' => 'invoiceFilter',
                'label' => __('Invoice'),
                'allLabel' => __('All'),
                'filterLabel' => match ($this->invoiceFilter) {
                    '0' => __('Not Invoiced'),
                    '1' => __('Invoiced'),
                    default => __('Invoice'),
                },
                'options' => ['0' => __('Not Invoiced'), '1' => __('Invoiced')],
                'selected' => $this->invoiceFilter,
                'route' => '',
            ],
        ];
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

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->serviceFilter !== '') {
            $query->where('interview_id', $this->serviceFilter);
        }

        if ($this->invoiceFilter !== '') {
            $query->where('invoice_sent', (int) $this->invoiceFilter);
        }

        return $this->sortQuery($query);
    }

    protected function getData(): CursorPaginator|LengthAwarePaginator|Paginator
    {
        if (! Schema::hasTable('candidates')) {
            $perPage = $this->perPage === __('All') ? 999999 : (int) $this->perPage;

            return new ConcretePaginator([], 0, $perPage, 1);
        }

        return parent::getData();
    }

    // -------------------------------------------------------------------------
    // Column renderers
    // -------------------------------------------------------------------------

    public function renderOrderIdColumn(Candidate $candidate): string
    {
        $editUrl = e(route($this->panelPrefix . '.candidates.edit', $candidate->id));

        return '<a href="' . $editUrl . '"
            class="font-mono text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300"
        >' . e($candidate->order_id) . '</a>';
    }

    public function renderNameColumn(Candidate $candidate): string
    {
        $name = e(trim($candidate->name . ' ' . $candidate->surname));
        $id = $candidate->id;

        // Dispatches both:
        // 1. Livewire event → CandidatePanel#[On('openCandidatePanel')] loads the data
        // 2. Browser event  → Alpine x-data in index.blade.php opens the slide-over
        $onclick = "Livewire.dispatch('openCandidatePanel',{id:{$id}});"
            . "window.dispatchEvent(new CustomEvent('open-candidate-panel'))";

        return '<button type="button"'
            . ' data-candidate-id="' . $id . '"'
            . ' onclick="' . $onclick . '"'
            . ' class="text-left text-sm font-medium text-gray-800 hover:text-indigo-600'
            . ' hover:underline dark:text-gray-100 dark:hover:text-indigo-400">'
            . $name . '</button>';
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
        $color = $candidate->statusRelation?->color;

        if ($color) {
            return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                style="background-color:' . e($color) . '">' . e($label) . '</span>';
        }

        return '<span class="text-sm">' . e($label) . '</span>';
    }

    public function renderBookedColumn(Candidate $candidate): string
    {
        if (empty($candidate->booked)) {
            return '<span class="text-xs text-gray-400">—</span>';
        }

        return '<span class="text-sm">' . e($candidate->booked->format('d M Y')) . '</span>';
    }

    // -------------------------------------------------------------------------
    // Routes / permissions
    // -------------------------------------------------------------------------

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
            'edit' => $this->panelPrefix . '.candidates.edit',
        ];
    }

    protected function getNewResourceLinkRouteName(): string
    {
        return $this->panelPrefix . '.candidates.create';
    }
}
