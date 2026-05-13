<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateHistory;
use App\Models\CompanyManager;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /** Status IDs that trigger the 14-day auto-archive window */
    private const ARCHIVE_STATUS_IDS = [4, 7, 9, 21, 22, 37, 40, 42, 52, 55, 56];

    public function index(): View
    {
        $userId      = Auth::id();
        $customerIds = $this->resolveCustomerIds($userId);
        $isManager   = $this->isCompanyManager($userId);

        // ── Explicitly expired orders ────────────────────────────────────────
        $archived = Candidate::query()
            ->whereIn('candidates.cus_id', $customerIds)
            ->where('candidates.expired', 1)
            ->leftJoin('service_types',      'candidates.interview_id',           '=', 'service_types.id')
            ->leftJoin('service_categories', 'service_types.service_category_id', '=', 'service_categories.id')
            ->leftJoin('statuses',           'candidates.status',                 '=', 'statuses.id')
            ->leftJoin('customers',          'candidates.cus_id',                 '=', 'customers.id')
            ->select(
                'candidates.id',
                'candidates.order_id',
                'candidates.name',
                'candidates.surname',
                'candidates.status',
                'candidates.booked',
                'candidates.delivery_date',
                'candidates.created_at',
                'candidates.created',
                'service_types.name        as service_name',
                'service_categories.name   as service_category_name',
                'statuses.status           as status_title',
                'statuses.color            as status_color',
                'customers.company         as company_name',
            )
            ->orderByDesc('candidates.created_at')
            ->get();

        // ── Auto-archived: expired=0 but window has closed (>14 days) ────────
        $autoArchived = Candidate::query()
            ->whereIn('candidates.cus_id', $customerIds)
            ->where('candidates.expired', 0)
            ->whereIn('candidates.status', self::ARCHIVE_STATUS_IDS)
            ->leftJoin('service_types',      'candidates.interview_id',           '=', 'service_types.id')
            ->leftJoin('service_categories', 'service_types.service_category_id', '=', 'service_categories.id')
            ->leftJoin('statuses',           'candidates.status',                 '=', 'statuses.id')
            ->leftJoin('customers',          'candidates.cus_id',                 '=', 'customers.id')
            ->select(
                'candidates.id',
                'candidates.order_id',
                'candidates.name',
                'candidates.surname',
                'candidates.status',
                'candidates.booked',
                'candidates.delivery_date',
                'candidates.created_at',
                'candidates.created',
                'service_types.name        as service_name',
                'service_categories.name   as service_category_name',
                'statuses.status           as status_title',
                'statuses.color            as status_color',
                'customers.company         as company_name',
            )
            ->get()
            ->filter(function ($order) {
                $last = CandidateHistory::where('order_id', $order->id)
                    ->orderByDesc('id')->first();
                if (! $last) {
                    return false;
                }
                $elapsed = Carbon::parse($last->date_time)->diffInDays(now());
                return $elapsed >= 14;
            });

        $orders = $archived->concat($autoArchived)
            ->sortByDesc('created_at')
            ->values();

        return view('customer.history.index', compact('orders', 'isManager'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveCustomerIds(int $userId): array
    {
        $customer = Customer::where('user_id', $userId)->first();
        if (! $customer) return [];

        $ids = [$customer->id];

        $manager = CompanyManager::where('cus_id', $customer->id)->first();
        if ($manager && $manager->company) {
            $companyIds = Customer::whereRaw('TRIM(company) = ?', [trim($manager->company)])
                ->pluck('id')->toArray();
            $ids = array_merge($ids, $companyIds);
        }

        if ($customer->groups) {
            foreach (explode(',', $customer->groups) as $group) {
                $groupIds = Customer::where('groups', 'like', '%' . trim($group) . '%')
                    ->pluck('id')->toArray();
                $ids = array_merge($ids, $groupIds);
            }
        }

        return array_values(array_unique($ids));
    }

    private function isCompanyManager(int $userId): bool
    {
        $customer = Customer::where('user_id', $userId)->first();
        return $customer
            ? CompanyManager::where('cus_id', $customer->id)->whereNotNull('company')->exists()
            : false;
    }
}
