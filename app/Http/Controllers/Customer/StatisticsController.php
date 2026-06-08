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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsController extends Controller
{
    // ── Statuses that mean "approved" by variable ────────────────────────────
    private const APPROVED_VARIABLES = ['approved', 'approved_bc', 'Approved_followup'];
    private const DENIED_VARIABLES = ['denied', 'notshow_msg_follow'];
    private const CANCEL_VARIABLES = ['bkcanceledbycustomer', 'canceledbycustomer'];

    // History description substrings (same as old portal)
    private const INVESTIGATION_PHRASES = ['candidate is under investigation', 'under investigation'];
    private const APPROVED_PHRASES = ['candidate has been approved', 'kandidaten har genomfört intervjun'];
    private const DENIED_PHRASES = ['candidate has been denied', 'denied for followup'];
    private const CANCEL_PHRASES = ['canceled by customer', 'cancelled by customer'];
    private const DEVIATION_PHRASES = ['deviation is found', 'a deviation'];

    // ────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $userId = Auth::id();
        $customerId = $this->getCustomerId($userId);
        $customerIds = $this->resolveCustomerIds($userId);

        // Service categories available to this customer
        $serviceCategories = ServiceCategory::whereHas('serviceTypes', function ($q) use ($customerId) {
            $q->whereHas('customers', fn ($q2) => $q2->where('customers.id', $customerId));
        })->orderBy('name')->get();

        // Distinct departments used by this customer's orders
        $departments = Candidate::whereIn('cus_id', $customerIds)
            ->whereNotNull('dep_id')
            ->where('dep_id', '!=', '')
            ->where('dep_id', '!=', '0')
            ->distinct()
            ->pluck('dep_id');

        return view('customer.statistics.index', compact('serviceCategories', 'departments'));
    }

    // ── AJAX data endpoint ───────────────────────────────────────────────────

    public function data(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $customerIds = $this->resolveCustomerIds($userId);
        $user = Auth::user();

        // ── Build base query ─────────────────────────────────────────────────
        $query = Candidate::query()
            ->whereIn('candidates.cus_id', $customerIds)
            ->where('candidates.expired', 0)
            ->leftJoin('service_types', 'candidates.interview_id', '=', 'service_types.id')
            ->leftJoin('service_categories', 'service_types.service_category_id', '=', 'service_categories.id')
            ->leftJoin('statuses', 'candidates.status', '=', 'statuses.id')
            ->select(
                'candidates.id',
                'candidates.status',
                'candidates.booked',
                'candidates.delivery_date',
                'candidates.dep_id',
                'service_categories.id   as service_category_id',
                'service_categories.name as service_category_name',
                'statuses.variable       as status_variable',
            );

        // ── Filters ──────────────────────────────────────────────────────────
        if ($catId = $request->input('service_id')) {
            $query->where('service_categories.id', $catId);
        }

        if ($depId = $request->input('department_id')) {
            $query->where('candidates.dep_id', $depId);
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $isBkOnly = $request->input('service_id') && optional(
            ServiceCategory::find($request->input('service_id'))
        )->id === 2; // category 2 = background check

        if ($dateFrom || $dateTo) {
            $query->where(function ($q) use ($dateFrom, $dateTo, $isBkOnly) {
                if ($isBkOnly) {
                    // BK: filter on delivery_date
                    if ($dateFrom) {
                        $q->where('candidates.delivery_date', '>=', $dateFrom);
                    }
                    if ($dateTo) {
                        $q->where('candidates.delivery_date', '<=', $dateTo);
                    }
                } else {
                    // Others: filter on booked OR delivery_date
                    $q->where(function ($inner) use ($dateFrom, $dateTo) {
                        if ($dateFrom) {
                            $inner->where('candidates.booked', '>=', $dateFrom)
                                  ->orWhere('candidates.delivery_date', '>=', $dateFrom);
                        }
                        if ($dateTo) {
                            $inner->where('candidates.booked', '<=', $dateTo)
                                  ->orWhere('candidates.delivery_date', '<=', $dateTo);
                        }
                    });
                }
            });
        }

        $candidates = $query->get();

        // ── Resolve all statuses for variable-based checks ───────────────────
        $approvedIds = Status::whereIn('variable', self::APPROVED_VARIABLES)->pluck('id')->all();
        $deniedIds = Status::whereIn('variable', self::DENIED_VARIABLES)->pluck('id')->all();
        $cancelIds = Status::whereIn('variable', self::CANCEL_VARIABLES)->pluck('id')->all();

        // ── Build per-category metrics ────────────────────────────────────────
        $byCategory = [];

        foreach ($candidates as $candidate) {
            $catId = $candidate->service_category_id ?? 0;
            $catName = $candidate->service_category_name ?? 'Unknown';

            if (! isset($byCategory[$catId])) {
                $byCategory[$catId] = $this->emptyMetrics($catId, $catName);
            }

            $byCategory[$catId]['total_orders']++;

            // Load this candidate's history descriptions
            $historyRows = CandidateHistory::where('order_id', $candidate->id)
                ->orderBy('date_time')
                ->pluck('desc')
                ->map(fn ($d) => mb_strtolower($d ?? ''))
                ->all();

            // Detect flags from history text (mirrors old portal logic)
            $hasInvestigation = false;
            $everApproved = false;
            $everRejected = false;
            $cancelledByCustomer = false;
            $hasDeviation = false;

            foreach ($historyRows as $desc) {
                foreach (self::INVESTIGATION_PHRASES as $p) {
                    if (str_contains($desc, $p)) {
                        $hasInvestigation = true;
                        break;
                    }
                }
                foreach (self::APPROVED_PHRASES as $p) {
                    if (str_contains($desc, $p)) {
                        $everApproved = true;
                        break;
                    }
                }
                foreach (self::DENIED_PHRASES as $p) {
                    if (str_contains($desc, $p)) {
                        $everRejected = true;
                        break;
                    }
                }
                foreach (self::CANCEL_PHRASES as $p) {
                    if (str_contains($desc, $p)) {
                        $cancelledByCustomer = true;
                        break;
                    }
                }
                foreach (self::DEVIATION_PHRASES as $p) {
                    if (str_contains($desc, $p)) {
                        $hasDeviation = true;
                        break;
                    }
                }
            }

            // Fall back to current status variable when history lacks text matches
            if (! $everApproved && in_array($candidate->status, $approvedIds)) {
                $everApproved = true;
            }
            if (! $everRejected && in_array($candidate->status, $deniedIds)) {
                $everRejected = true;
            }
            if (! $cancelledByCustomer && in_array($candidate->status, $cancelIds)) {
                $cancelledByCustomer = true;
            }

            // ── Classify ─────────────────────────────────────────────────────
            if ($cancelledByCustomer) {
                $byCategory[$catId]['cancelled_by_customer']++;
                continue;
            }

            // Background-check deviation + approved: keep only more recent signal
            if ($catId === 2 && $hasDeviation && $everApproved) {
                // Both present — count as approved (admin decision takes precedence)
                $hasDeviation = false;
            }

            if ($hasDeviation) {
                $byCategory[$catId]['deviation']++;
            } elseif ($hasInvestigation && $everApproved) {
                $byCategory[$catId]['under_investigation_then_approved']++;
            } elseif ($hasInvestigation && $everRejected) {
                $byCategory[$catId]['under_investigation_then_rejected']++;
            } elseif ($hasInvestigation) {
                $byCategory[$catId]['under_investigation_current']++;
            } elseif ($everApproved) {
                $byCategory[$catId]['immediate_approved']++;
            }
        }

        // ── Summary row ──────────────────────────────────────────────────────
        $summary = $this->emptyMetrics(0, 'Total');
        foreach ($byCategory as $row) {
            foreach (array_keys($summary) as $k) {
                if (is_int($summary[$k])) {
                    $summary[$k] += $row[$k];
                }
            }
        }

        // ── Visible metrics (hide deviation if no BK orders) ─────────────────
        $hasBk = collect($byCategory)->contains(fn ($r) => ($r['service_category_id'] ?? 0) === 3);
        $visible = [
            'total_orders',
            'immediate_approved',
            'under_investigation_current',
            'under_investigation_then_approved',
            'under_investigation_then_rejected',
            'cancelled_by_customer',
        ];
        if ($hasBk || ($request->input('service_id') == 2)) {
            $visible[] = 'deviation';
        }

        return response()->json([
            'data' => array_values($byCategory),
            'summary' => $summary,
            'visible_metrics' => $visible,
            'metric_labels' => $this->metricLabels(),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
    }

    // ── CSV Export ───────────────────────────────────────────────────────────

    public function export(Request $request): StreamedResponse
    {
        $stats = $this->data($request);
        $json = json_decode($stats->getContent(), true);
        $rows = $json['data'] ?? [];
        $summary = $json['summary'] ?? [];
        $visible = $json['visible_metrics'] ?? [];
        $labels = $json['metric_labels'] ?? [];

        $user = Auth::user();
        $customer = Customer::where('user_id', $user->id)->first();
        $period = ($request->input('date_from') ?? '—') . ' to ' . ($request->input('date_to') ?? '—');

        $callback = function () use ($rows, $summary, $visible, $labels, $user, $customer, $period) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fputs($out, "\xEF\xBB\xBF");

            // Header info
            fputcsv($out, []);
            fputcsv($out, [
                'Recway — Statistics',
                'Customer: ' . $user->name,
                'Company: '  . ($customer?->company ?? ''),
                'Period: '   . $period,
            ]);
            fputcsv($out, []);

            // Column headers
            $cols = ['Service Category'];
            foreach ($visible as $key) {
                $cols[] = $labels[$key] ?? $key;
            }
            fputcsv($out, $cols);

            // Data rows
            foreach ($rows as $row) {
                $line = [$row['service_category_name']];
                foreach ($visible as $key) {
                    $line[] = $row[$key] ?? 0;
                }
                fputcsv($out, $line);
            }

            // Summary
            fputcsv($out, []);
            fputcsv($out, ['TOTAL']);
            $sumLine = ['All'];
            foreach ($visible as $key) {
                $sumLine[] = $summary[$key] ?? 0;
            }
            fputcsv($out, $sumLine);

            fclose($out);
        };

        $filename = 'recway-statistics-' . now()->format('Y-m-d') . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function emptyMetrics(int $catId, string $catName): array
    {
        return [
            'service_category_id' => $catId,
            'service_category_name' => $catName,
            'total_orders' => 0,
            'immediate_approved' => 0,
            'under_investigation_current' => 0,
            'under_investigation_then_approved' => 0,
            'under_investigation_then_rejected' => 0,
            'cancelled_by_customer' => 0,
            'deviation' => 0,
        ];
    }

    private function metricLabels(): array
    {
        return [
            'total_orders' => __('Total Orders'),
            'immediate_approved' => __('Immediate Approved'),
            'under_investigation_current' => __('Under Investigation'),
            'under_investigation_then_approved' => __('Investigated → Approved'),
            'under_investigation_then_rejected' => __('Investigated → Rejected'),
            'cancelled_by_customer' => __('Cancelled by Customer'),
            'deviation' => __('Deviation'),
        ];
    }

    private function getCustomerId(int $userId): ?int
    {
        return Customer::where('user_id', $userId)->value('id');
    }

    private function resolveCustomerIds(int $userId): array
    {
        $customer = Customer::where('user_id', $userId)->first();
        if (! $customer) {
            return [];
        }

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
}
