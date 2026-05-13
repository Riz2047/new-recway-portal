<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Services\Invoice\InvoiceService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService)
    {
    }

    // -------------------------------------------------------------------------
    // Customer Invoices — list all generated invoices
    // -------------------------------------------------------------------------

    public function index(Request $request): Renderable
    {
        $this->authorize('viewAny', Customer::class);
        $this->setBreadcrumbTitle(__('Customer Invoices'));

        $filter = $request->input('status', 'all');
        $period = $request->input('period', 'all');
        $search = $request->input('search', '');
        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        $query = CustomerInvoice::with('customer.user')
            ->when($filter !== 'all', fn ($q) => $q->where('status', $filter))
            ->when($period !== 'all', fn ($q) => $q->where('period', $period))
            ->when($search, fn ($q) => $q->whereHas('customer', function ($cq) use ($search) {
                $cq->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                   ->orWhere('company', 'like', "%{$search}%");
            }))
            ->orderByDesc('id');

        $invoices = $query->paginate(25)->withQueryString();

        $counts = Schema::hasTable('customer_invoices')
            ? [
                'all' => CustomerInvoice::count(),
                'to_be_invoiced' => CustomerInvoice::where('status', CustomerInvoice::STATUS_TO_BE_INVOICED)->count(),
                'sent' => CustomerInvoice::where('status', CustomerInvoice::STATUS_SENT)->count(),
            ]
            : ['all' => 0, 'to_be_invoiced' => 0, 'sent' => 0];

        return $this->renderViewWithBreadcrumbs('backend.pages.invoices.index', compact(
            'invoices',
            'counts',
            'filter',
            'period',
            'search',
            'prefix'
        ));
    }

    // -------------------------------------------------------------------------
    // Invoice detail / show
    // -------------------------------------------------------------------------

    public function show(Request $request, CustomerInvoice $invoice): Renderable
    {
        $this->authorize('viewAny', Customer::class);

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        $this->setBreadcrumbTitle(__('Invoice #:id', ['id' => $invoice->id]))
            ->addBreadcrumbItem(__('Customer Invoices'), route($prefix . '.invoices.index'));

        $invoice->load('customer.user');
        $candidates = $invoice->getCandidates();

        return $this->renderViewWithBreadcrumbs('backend.pages.invoices.show', compact(
            'invoice',
            'candidates',
            'prefix'
        ));
    }

    // -------------------------------------------------------------------------
    // Mark invoice as sent
    // -------------------------------------------------------------------------

    public function markSent(Request $request, CustomerInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', Customer::class);

        $invoice->markAsSent();

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        return back()->with('success', __('Invoice #:id marked as sent.', ['id' => $invoice->id]));
    }

    // -------------------------------------------------------------------------
    // Mark invoice as pending (revert)
    // -------------------------------------------------------------------------

    public function markPending(Request $request, CustomerInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', Customer::class);

        $invoice->update(['status' => CustomerInvoice::STATUS_TO_BE_INVOICED, 'sent_at' => null]);

        return back()->with('success', __('Invoice #:id reverted to pending.', ['id' => $invoice->id]));
    }

    // -------------------------------------------------------------------------
    // Manually trigger invoice generation for a single customer
    // -------------------------------------------------------------------------

    public function generateForCustomer(Request $request): RedirectResponse
    {
        $this->authorize('update', Customer::class);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $invoice = $this->invoiceService->generateForCustomer($customer, forceRun: true);

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        if ($invoice) {
            return back()->with('success', __('Invoice #:id generated for :name.', [
                'id' => $invoice->id,
                'name' => $customer->user?->name ?? "Customer #{$customer->id}",
            ]));
        }

        return back()->with('info', __('No billable candidates found for this customer.'));
    }

    // -------------------------------------------------------------------------
    // Candidates pending invoice (invoice_sent = 0, not expired)
    // -------------------------------------------------------------------------

    public function pendingCandidates(Request $request): Renderable
    {
        $this->authorize('viewAny', Customer::class);

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        $this->setBreadcrumbTitle(__('Pending Invoices'))
            ->addBreadcrumbItem(__('Customer Invoices'), route($prefix . '.invoices.index'));

        $search = $request->input('search', '');
        $periodFilter = $request->input('period', 'all');

        $query = Candidate::with(['customer.user', 'serviceType', 'statusRelation'])
            ->where('expired', 0)
            ->where('invoice_sent', 0)
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('order_id', 'like', "%{$search}%")
                   ->orWhere('name', 'like', "%{$search}%")
                   ->orWhere('surname', 'like', "%{$search}%");
            }))
            ->when($periodFilter !== 'all', fn ($q) => $q->whereHas(
                'customer',
                fn ($cq) =>
                $cq->where('invoice_period', $periodFilter)
            ))
            ->orderByDesc('booked');

        $candidates = $query->paginate(30)->withQueryString();

        // Count by invoice_period for filter tabs.
        $periodCounts = Candidate::where('expired', 0)->where('invoice_sent', 0)
            ->join('customers', 'candidates.cus_id', '=', 'customers.id')
            ->selectRaw('customers.invoice_period, count(*) as total')
            ->groupBy('customers.invoice_period')
            ->pluck('total', 'customers.invoice_period')
            ->toArray();

        return $this->renderViewWithBreadcrumbs('backend.pages.invoices.pending', compact(
            'candidates',
            'search',
            'periodFilter',
            'periodCounts',
            'prefix'
        ));
    }

    // -------------------------------------------------------------------------
    // Bulk mark-as-invoice-sent from pending list
    // -------------------------------------------------------------------------

    public function bulkMarkSent(Request $request): RedirectResponse
    {
        $this->authorize('update', Customer::class);

        $ids = array_filter((array) $request->input('ids', []), 'is_numeric');

        if (empty($ids)) {
            return back()->with('error', __('No candidates selected.'));
        }

        $count = Candidate::whereIn('id', $ids)
            ->where('invoice_sent', 0)
            ->update([
                'invoice_sent' => 1,
                'invoice_date' => now()->toDateString(),
            ]);

        return back()->with('success', __(':count candidate(s) marked as invoice sent.', ['count' => $count]));
    }
}
