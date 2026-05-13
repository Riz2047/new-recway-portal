<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\Status;
use App\Services\AnalyticsService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
    }

    // -------------------------------------------------------------------------
    // Main dashboard
    // -------------------------------------------------------------------------

    public function index(Request $request): Renderable
    {
        $this->authorize('viewAny', Customer::class);
        $this->setBreadcrumbTitle(__('Analytics'));

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        // Filter values from query string (or defaults).
        $filters = $this->extractFilters($request);

        // Base data for filter dropdowns.
        $customers = Schema::hasTable('customers')
            ? Customer::with('user')->get()->sortBy(fn ($c) => $c->user?->name ?? '')->values()
            : collect();

        $companies = Schema::hasTable('customers')
            ? DB::table('customers')->whereNotNull('company')->where('company', '!=', '')->distinct()->pluck('company')->sort()->values()
            : collect();

        $statuses = Schema::hasTable('statuses') ? Status::orderBy('status')->get(['id', 'status', 'color']) : collect();

        $serviceCategories = Schema::hasTable('service_categories') ? ServiceCategory::orderBy('name')->get(['id', 'name']) : collect();

        // Quick period counts (always unfiltered for the top cards).
        $periodCounts = $this->analytics->getOrderCountsByPeriod();

        // Filtered summary stats.
        $summary = $this->analytics->getSummaryStats($filters);

        // Status breakdown (pie chart).
        $statusBreakdown = $this->analytics->getStatusBreakdown($filters);

        // Tables.
        $customersWithOrders = $this->analytics->getCustomersWithOrders($filters);
        $customersWithNoOrders = $this->analytics->getCustomersWithNoOrders();
        $companyStats = $this->analytics->getCompanyStats($filters);
        $uninvoicedOrders = $this->analytics->getUninvoicedOrders($filters);

        // Chart data (JSON for JS).
        $chartData = $this->analytics->getDailyChartData($filters);

        return $this->renderViewWithBreadcrumbs('backend.pages.analytics.index', compact(
            'prefix',
            'filters',
            'customers',
            'companies',
            'statuses',
            'serviceCategories',
            'periodCounts',
            'summary',
            'statusBreakdown',
            'customersWithOrders',
            'customersWithNoOrders',
            'companyStats',
            'uninvoicedOrders',
            'chartData'
        ));
    }

    // -------------------------------------------------------------------------
    // JSON endpoint — chart + table data with filters (AJAX refresh)
    // -------------------------------------------------------------------------

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $filters = $this->extractFilters($request);

        return response()->json([
            'summary' => $this->analytics->getSummaryStats($filters),
            'chart_data' => $this->analytics->getDailyChartData($filters),
            'status_breakdown' => $this->analytics->getStatusBreakdown($filters),
            'customers_with_orders' => $this->analytics->getCustomersWithOrders($filters),
            'company_stats' => $this->analytics->getCompanyStats($filters),
            'uninvoiced_orders' => $this->analytics->getUninvoicedOrders($filters),
        ]);
    }

    // -------------------------------------------------------------------------
    // Mark a single order as invoice sent (from uninvoiced table)
    // -------------------------------------------------------------------------

    public function markInvoiceSent(Request $request): JsonResponse
    {
        $this->authorize('update', Customer::class);

        $validated = $request->validate([
            'order_id' => ['required', 'string'],
            'invoice_sent' => ['required', 'boolean'],
        ]);

        if (! Schema::hasTable('candidates')) {
            return response()->json(['success' => false, 'message' => 'Table not available.'], 422);
        }

        $updated = Candidate::where('order_id', $validated['order_id'])
            ->update([
                'invoice_sent' => $validated['invoice_sent'] ? 1 : 0,
                'invoice_date' => $validated['invoice_sent'] ? now()->toDateString() : null,
            ]);

        return response()->json([
            'success' => $updated > 0,
            'message' => $updated > 0 ? __('Invoice status updated.') : __('Order not found.'),
            'date' => $validated['invoice_sent'] ? now()->toDateString() : null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function extractFilters(Request $request): array
    {
        return [
            'start_date' => $request->input('start_date', now()->subDays(29)->toDateString()),
            'end_date' => $request->input('end_date', now()->toDateString()),
            'customer_id' => $request->input('customer_id'),
            'company' => $request->input('company'),
            'service_category_id' => $request->input('service_category_id'),
            'status_id' => $request->input('status_id'),
            'created_from' => $request->input('created_from'),
            'created_to' => $request->input('created_to'),
        ];
    }
}
