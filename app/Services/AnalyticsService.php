<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Provides all data queries for the Analytics dashboard.
 * All methods accept a $filters array with optional keys:
 *   start_date, end_date, customer_id, company, service_category_id,
 *   status_id, created_from, created_to
 */
class AnalyticsService
{
    // -------------------------------------------------------------------------
    // Summary stat cards
    // -------------------------------------------------------------------------

    /** @return array{created:int, booked:int, approved:int, canceled:int, total:int} */
    public function getSummaryStats(array $filters = []): array
    {
        if (! Schema::hasTable('candidates')) {
            return ['created' => 0, 'booked' => 0, 'approved' => 0, 'canceled' => 0, 'total' => 0];
        }

        $base = $this->baseQuery($filters);

        $total = (clone $base)->where('expired', 0)->count();
        $booked = (clone $base)->where('expired', 0)->whereNotNull('booked')->count();

        $approvedIds = Status::whereIn('variable', ['approved', 'approval_received'])->pluck('id');
        $canceledIds = Status::where('variable', 'canceled')->pluck('id');

        $approved = (clone $base)->whereIn('status', $approvedIds)->count();
        $canceled = (clone $base)->whereIn('status', $canceledIds)->count();

        $created = (clone $base)->count(); // all in date range regardless of expired

        return compact('created', 'booked', 'approved', 'canceled', 'total');
    }

    // -------------------------------------------------------------------------
    // Chart data: daily counts per status category
    // -------------------------------------------------------------------------

    /**
     * Returns daily counts suitable for Chart.js line charts.
     * Each entry: {date: 'YYYY-MM-DD', created: N, booked: N, approved: N, canceled: N}
     *
     * @return array<array{date:string,created:int,booked:int,approved:int,canceled:int}>
     */
    public function getDailyChartData(array $filters = []): array
    {
        if (! Schema::hasTable('candidates')) {
            return [];
        }

        $start = Carbon::parse($filters['start_date'] ?? now()->subDays(29))->startOfDay();
        $end = Carbon::parse($filters['end_date'] ?? now())->endOfDay();

        $approvedIds = Status::whereIn('variable', ['approved', 'approval_received'])->pluck('id')->toArray();
        $canceledIds = Status::where('variable', 'canceled')->pluck('id')->toArray();

        // Build the base query for this date range
        $baseFilters = $filters;
        unset($baseFilters['start_date'], $baseFilters['end_date']); // handled separately

        $rows = DB::table('candidates as c')
            ->leftJoin('customers as cu', 'c.cus_id', '=', 'cu.id')
            ->leftJoin('users as u', 'cu.user_id', '=', 'u.id')
            ->where('c.created_at', '>=', $start)
            ->where('c.created_at', '<=', $end)
            ->when(
                isset($filters['customer_id']) && $filters['customer_id'],
                fn ($q) =>
                $q->where('c.cus_id', $filters['customer_id'])
            )
            ->when(
                isset($filters['company']) && $filters['company'],
                fn ($q) =>
                $q->where('cu.company', $filters['company'])
            )
            ->when(
                isset($filters['service_category_id']) && $filters['service_category_id'],
                fn ($q) =>
                $q->whereExists(
                    fn ($sq) =>
                    $sq->from('service_types')
                        ->whereColumn('service_types.id', 'c.interview_id')
                        ->where('service_types.service_category_id', $filters['service_category_id'])
                )
            )
            ->selectRaw(
                "DATE(c.created_at) as date, COUNT(*) as created_count,
                SUM(CASE WHEN c.booked IS NOT NULL THEN 1 ELSE 0 END) as booked_count,
                SUM(CASE WHEN c.status IN (" . (empty($approvedIds) ? '0' : implode(',', $approvedIds)) . ") THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN c.status IN (" . (empty($canceledIds) ? '0' : implode(',', $canceledIds)) . ") THEN 1 ELSE 0 END) as canceled_count"
            )
            ->groupByRaw('DATE(c.created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill every day in range, even with 0s
        $data = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $d = $cursor->toDateString();
            $row = $rows->get($d);
            $data[] = [
                'date' => $d,
                'created' => $row ? (int) $row->created_count : 0,
                'booked' => $row ? (int) $row->booked_count : 0,
                'approved' => $row ? (int) $row->approved_count : 0,
                'canceled' => $row ? (int) $row->canceled_count : 0,
            ];
            $cursor->addDay();
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Customers with orders (ranking)
    // -------------------------------------------------------------------------

    /** @return Collection<int, object{name:string, company:string, order_count:int}> */
    public function getCustomersWithOrders(array $filters = []): Collection
    {
        if (! Schema::hasTable('candidates')) {
            return collect();
        }

        return DB::table('candidates as c')
            ->join('customers as cu', 'c.cus_id', '=', 'cu.id')
            ->join('users as u', 'cu.user_id', '=', 'u.id')
            ->where('c.expired', 0)
            ->when(
                isset($filters['start_date']) && $filters['start_date'],
                fn ($q) =>
                $q->where('c.created_at', '>=', $filters['start_date'])
            )
            ->when(
                isset($filters['end_date']) && $filters['end_date'],
                fn ($q) =>
                $q->where('c.created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay())
            )
            ->selectRaw('u.name, cu.company, COUNT(c.id) as order_count, cu.id as customer_id')
            ->groupBy('cu.id', 'u.name', 'cu.company')
            ->orderByDesc('order_count')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Customers with NO orders this year
    // -------------------------------------------------------------------------

    /** @return Collection<int, object{name:string, email:string}> */
    public function getCustomersWithNoOrders(): Collection
    {
        if (! Schema::hasTable('customers')) {
            return collect();
        }

        return DB::table('customers as cu')
            ->join('users as u', 'cu.user_id', '=', 'u.id')
            ->whereNotExists(
                fn ($q) =>
                $q->from('candidates')
                    ->whereColumn('candidates.cus_id', 'cu.id')
                    ->where('candidates.expired', 0)
                    ->whereYear('candidates.created_at', now()->year)
            )
            ->select(['u.name', 'u.email', 'cu.company'])
            ->orderBy('u.name')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Company order stats
    // -------------------------------------------------------------------------

    /** @return Collection<int, object{company:string, order_count:int}> */
    public function getCompanyStats(array $filters = []): Collection
    {
        if (! Schema::hasTable('candidates')) {
            return collect();
        }

        return DB::table('candidates as c')
            ->join('customers as cu', 'c.cus_id', '=', 'cu.id')
            ->where('c.expired', 0)
            ->when(
                isset($filters['start_date']) && $filters['start_date'],
                fn ($q) =>
                $q->where('c.created_at', '>=', $filters['start_date'])
            )
            ->when(
                isset($filters['end_date']) && $filters['end_date'],
                fn ($q) =>
                $q->where('c.created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay())
            )
            ->whereNotNull('cu.company')
            ->where('cu.company', '!=', '')
            ->selectRaw('cu.company, COUNT(c.id) as order_count')
            ->groupBy('cu.company')
            ->orderByDesc('order_count')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Uninvoiced orders
    // -------------------------------------------------------------------------

    /** @return Collection */
    public function getUninvoicedOrders(array $filters = []): Collection
    {
        if (! Schema::hasTable('candidates')) {
            return collect();
        }

        return DB::table('candidates as c')
            ->join('customers as cu', 'c.cus_id', '=', 'cu.id')
            ->join('users as u', 'cu.user_id', '=', 'u.id')
            ->leftJoin('statuses as st', 'c.status', '=', 'st.id')
            ->leftJoin('service_types as svc', 'c.interview_id', '=', 'svc.id')
            ->leftJoin('service_categories as sc', 'svc.service_category_id', '=', 'sc.id')
            ->where('c.expired', 0)
            ->where('c.invoice_sent', 0)
            ->when(
                isset($filters['start_date']) && $filters['start_date'],
                fn ($q) =>
                $q->where('c.booked', '>=', $filters['start_date'])
            )
            ->when(
                isset($filters['end_date']) && $filters['end_date'],
                fn ($q) =>
                $q->where('c.booked', '<=', $filters['end_date'])
            )
            ->when(
                isset($filters['customer_id']) && $filters['customer_id'],
                fn ($q) =>
                $q->where('c.cus_id', $filters['customer_id'])
            )
            ->when(
                isset($filters['company']) && $filters['company'],
                fn ($q) =>
                $q->where('cu.company', $filters['company'])
            )
            ->when(
                isset($filters['service_category_id']) && $filters['service_category_id'],
                fn ($q) =>
                $q->where('svc.service_category_id', $filters['service_category_id'])
            )
            ->when(
                isset($filters['status_id']) && $filters['status_id'],
                fn ($q) =>
                $q->where('c.status', $filters['status_id'])
            )
            ->selectRaw('c.id, c.order_id, c.booked, c.delivery_date, c.invoice_sent,
                         u.name as customer_name, cu.company as customer_company,
                         st.status as status_name, st.color as status_color,
                         sc.name as service_category_name')
            ->orderBy('c.booked')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Status breakdown for pie/donut chart
    // -------------------------------------------------------------------------

    /** @return Collection<int, object{status:string, color:string, count:int}> */
    public function getStatusBreakdown(array $filters = []): Collection
    {
        if (! Schema::hasTable('candidates') || ! Schema::hasTable('statuses')) {
            return collect();
        }

        return DB::table('candidates as c')
            ->join('statuses as st', 'c.status', '=', 'st.id')
            ->where('c.expired', 0)
            ->when(
                isset($filters['start_date']) && $filters['start_date'],
                fn ($q) =>
                $q->where('c.created_at', '>=', $filters['start_date'])
            )
            ->when(
                isset($filters['end_date']) && $filters['end_date'],
                fn ($q) =>
                $q->where('c.created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay())
            )
            ->selectRaw('st.status, st.color, COUNT(c.id) as count')
            ->groupBy('st.id', 'st.status', 'st.color')
            ->orderByDesc('count')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Quick counts for the top cards (today / week / month)
    // -------------------------------------------------------------------------

    /** @return array{today:int, this_week:int, this_month:int, total:int} */
    public function getOrderCountsByPeriod(): array
    {
        if (! Schema::hasTable('candidates')) {
            return ['today' => 0, 'this_week' => 0, 'this_month' => 0, 'total' => 0];
        }

        $q = fn () => DB::table('candidates')->where('expired', 0);

        return [
            'today' => $q()->whereDate('created_at', today())->count(),
            'this_week' => $q()->where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => $q()->where('created_at', '>=', now()->startOfMonth())->count(),
            'total' => $q()->count(),
        ];
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function baseQuery(array $filters): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('candidates as c')
            ->leftJoin('customers as cu', 'c.cus_id', '=', 'cu.id');

        if (! empty($filters['start_date'])) {
            $q->where('c.created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        } else {
            // Default: last 30 days
            $q->where('c.created_at', '>=', now()->subDays(29)->startOfDay());
        }

        if (! empty($filters['end_date'])) {
            $q->where('c.created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        if (! empty($filters['customer_id'])) {
            $q->where('c.cus_id', $filters['customer_id']);
        }

        if (! empty($filters['company'])) {
            $q->where('cu.company', $filters['company']);
        }

        if (! empty($filters['service_category_id'])) {
            $q->whereExists(
                fn ($sq) =>
                $sq->from('service_types')
                    ->whereColumn('service_types.id', 'c.interview_id')
                    ->where('service_types.service_category_id', $filters['service_category_id'])
            );
        }

        if (! empty($filters['status_id'])) {
            $q->where('c.status', $filters['status_id']);
        }

        return $q;
    }
}
