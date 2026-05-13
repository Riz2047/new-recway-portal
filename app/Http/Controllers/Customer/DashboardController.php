<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateHistory;
use App\Models\CompanyManager;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Status IDs that start the 14-day archive countdown */
    private const ARCHIVE_STATUS_IDS = [4, 7, 9, 21, 22, 37, 40, 42, 52, 55, 56];

    public function index(): View
    {
        $userId     = Auth::id();
        $customerId = $this->getCustomerId($userId);

        // If no customer record yet, show empty dashboard
        if (! $customerId) {
            return view('customer.dashboard.index', [
                'recentOrders'      => collect(),
                'activeCount'       => 0,
                'archivedCount'     => 0,
                'catCounts'         => [],
                'serviceCategories' => ServiceCategory::all(),
                'months'            => collect(),
                'chartData'         => [],
                'statusBreakdown'   => [],
                'isManager'         => false,
            ]);
        }

        $customerIds = $this->resolveCustomerIds($customerId);
        $isManager   = $this->isCompanyManager($customerId);

        // ── Recent active orders ─────────────────────────────────────────────
        $recentOrders = Candidate::query()
            ->whereIn('candidates.cus_id', $customerIds)
            ->where('candidates.expired', 0)
            ->leftJoin('service_types',      'candidates.interview_id',           '=', 'service_types.id')
            ->leftJoin('service_categories', 'service_types.service_category_id', '=', 'service_categories.id')
            ->leftJoin('statuses',           'candidates.status',                 '=', 'statuses.id')
            ->leftJoin('users as staff',     'candidates.staff_id',               '=', 'staff.id')
            ->leftJoin('customers',          'candidates.cus_id',                 '=', 'customers.id')
            ->select(
                'candidates.id',
                'candidates.order_id',
                'candidates.name',
                'candidates.surname',
                'candidates.email',
                'candidates.status',
                'candidates.booked',
                'candidates.delivery_date',
                'candidates.cus_id',
                'candidates.created_at',
                'service_types.name        as service_name',
                'service_categories.id     as service_category_id',
                'service_categories.name   as service_category_name',
                'statuses.status           as status_title',
                'statuses.color            as status_color',
                'statuses.status_type',
                'staff.name                as staff_name',
                'customers.company         as company_name',
            )
            ->orderByDesc('candidates.created_at')
            ->get();

        $this->addArchiveCountdowns($recentOrders);

        // Remove orders whose 14-day window has already closed
        $recentOrders = $recentOrders->filter(
            fn ($o) => ($o->days_to_archive ?? '') !== 'expired'
        );

        // ── Counts ──────────────────────────────────────────────────────────
        $activeCount   = $recentOrders->count();
        $archivedCount = Candidate::whereIn('cus_id', $customerIds)
            ->where('expired', 1)->count();

        // ── Per-category counts ──────────────────────────────────────────────
        [$catCounts, $serviceCategories] = $this->categoryCounts($customerIds);

        // ── 12-month chart data ──────────────────────────────────────────────
        [$months, $chartData] = $this->buildChartData($customerIds, $serviceCategories);

        // ── Status breakdown ─────────────────────────────────────────────────
        $statusBreakdown = $this->statusBreakdown($recentOrders, $serviceCategories);

        // ── Statuses with counts (for filter buttons) ─────────────────────────
        $statusesWithCounts = $this->buildStatusesWithCounts($recentOrders, $serviceCategories);

        return view('customer.dashboard.index', compact(
            'recentOrders',
            'activeCount',
            'archivedCount',
            'catCounts',
            'serviceCategories',
            'months',
            'chartData',
            'statusBreakdown',
            'statusesWithCounts',
            'isManager',
        ));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * candidates.cus_id references customers.id, not users.id.
     * Returns customers.id for the currently authenticated user.
     */
    private function getCustomerId(int $userId): ?int
    {
        return Customer::where('user_id', $userId)->value('id');
    }

    /**
     * Returns all customers.id values visible to this customer
     * (own + company peers + group peers).
     */
    private function resolveCustomerIds(int $customerId): array
    {
        $ids = [$customerId];

        // Company manager scope
        $manager = CompanyManager::where('cus_id', $customerId)->first();
        if ($manager && $manager->company) {
            $companyIds = Customer::whereRaw('TRIM(company) = ?', [trim($manager->company)])
                ->pluck('id')->toArray();
            $ids = array_merge($ids, $companyIds);
        }

        // Group scope
        $customer = Customer::select('groups')->find($customerId);
        if ($customer && $customer->groups) {
            foreach (explode(',', $customer->groups) as $group) {
                $groupIds = Customer::where('groups', 'like', '%' . trim($group) . '%')
                    ->pluck('id')->toArray();
                $ids = array_merge($ids, $groupIds);
            }
        }

        return array_values(array_unique($ids));
    }

    private function isCompanyManager(int $customerId): bool
    {
        return CompanyManager::where('cus_id', $customerId)
            ->whereNotNull('company')->exists();
    }

    /** Attach a days_to_archive value to every order in the collection. */
    private function addArchiveCountdowns(Collection $orders): void
    {
        foreach ($orders as $order) {
            $order->days_to_archive = 'N/A';

            if (! in_array($order->status, self::ARCHIVE_STATUS_IDS)) {
                continue;
            }

            $last = CandidateHistory::where('order_id', $order->id)
                ->orderByDesc('id')->first();

            if ($last) {
                $remaining = 14 - (int) Carbon::parse($last->date_time)->diffInDays(now());
                $order->days_to_archive = $remaining > 0 ? $remaining : 'expired';
            }
        }
    }

    /** Active order counts per service category. */
    private function categoryCounts(array $customerIds): array
    {
        $rows = DB::table('candidates')
            ->join('statuses',           'candidates.status',       '=', 'statuses.id')
            ->join('service_categories', 'statuses.status_type',    '=', 'service_categories.id')
            ->whereIn('candidates.cus_id', $customerIds)
            ->where('candidates.expired', 0)
            ->select('service_categories.id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('service_categories.id')
            ->pluck('cnt', 'id');

        $serviceCategories = ServiceCategory::all();

        $catCounts = [];
        foreach ($serviceCategories as $cat) {
            $catCounts[$cat->id] = (int) $rows->get($cat->id, 0);
        }

        return [$catCounts, $serviceCategories];
    }

    /** Last 12 months labels + series data per service category. */
    private function buildChartData(array $customerIds, EloquentCollection $serviceCategories): array
    {
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('M Y'));
        }

        $rows = DB::table('candidates')
            ->join('statuses',           'candidates.status',       '=', 'statuses.id')
            ->join('service_categories', 'statuses.status_type',    '=', 'service_categories.id')
            ->whereIn('candidates.cus_id', $customerIds)
            ->where('candidates.expired', 0)
            ->whereRaw('candidates.created_at >= ?', [Carbon::now()->subMonths(11)->startOfMonth()])
            ->select(
                'service_categories.id   as cat_id',
                'service_categories.name as cat_name',
                DB::raw("DATE_FORMAT(candidates.created_at, '%b %Y') as month_label"),
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('service_categories.id', 'service_categories.name', 'month_label')
            ->get();

        $chartData = [];
        foreach ($serviceCategories as $cat) {
            $series = [];
            foreach ($months as $label) {
                $match   = $rows->first(fn ($r) => $r->cat_id === $cat->id && $r->month_label === $label);
                $series[] = $match ? (int) $match->cnt : 0;
            }
            $chartData[] = ['name' => $cat->name, 'data' => $series];
        }

        return [$months->values(), $chartData];
    }

    /**
     * Returns all statuses that appear in these orders, with per-status counts,
     * grouped by service_category_id — used for the filter buttons.
     */
    private function buildStatusesWithCounts(Collection $orders, EloquentCollection $serviceCategories): EloquentCollection
    {
        // Collect unique status IDs present in the current orders
        $statusIds = $orders->pluck('status')->unique()->filter()->values()->all();

        $statuses = Status::whereIn('id', $statusIds)
            ->orderBy('status')
            ->get();

        foreach ($statuses as $status) {
            $status->count = $orders->where('status', $status->id)->count();
        }

        return $statuses;
    }

    /** Per-status count breakdown grouped by service category. */
    private function statusBreakdown(Collection $orders, EloquentCollection $serviceCategories): array
    {
        $breakdown = [];
        foreach ($serviceCategories as $cat) {
            $rows = [];
            foreach (Status::where('status_type', $cat->id)->get() as $status) {
                $count = $orders->where('status', $status->id)->count();
                if ($count > 0) {
                    $rows[] = [
                        'label' => $status->status,
                        'color' => $status->color,
                        'count' => $count,
                    ];
                }
            }
            if (! empty($rows)) {
                $breakdown[$cat->name] = $rows;
            }
        }
        return $breakdown;
    }
}
