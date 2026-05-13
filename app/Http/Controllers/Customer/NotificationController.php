<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // ── Notifications page ───────────────────────────────────────────────────

    public function index(): View
    {
        $updates = DB::table('updates')
            ->where('visible', 1)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $locale   = session('locale', app()->getLocale());
        $isSv     = ($locale === 'sv' || $locale === 'swg');

        foreach ($updates as $u) {
            $u->title   = $isSv ? ($u->title_sv   ?: $u->title_en)   : $u->title_en;
            $u->content = $isSv ? ($u->content_sv ?: $u->content_en) : $u->content_en;
        }

        // Mark as read on page visit
        $this->markReadForCustomer();

        return view('customer.notifications.index', compact('updates'));
    }

    // ── AJAX: list updates ───────────────────────────────────────────────────

    public function list(): JsonResponse
    {
        $locale = session('locale', app()->getLocale());
        $isSv   = ($locale === 'sv' || $locale === 'swg');

        $titleCol   = $isSv ? 'title_sv'   : 'title_en';
        $contentCol = $isSv ? 'content_sv' : 'content_en';

        $updates = DB::table('updates')
            ->where('visible', 1)
            ->select(
                'id',
                DB::raw("$titleCol as title"),
                DB::raw("$contentCol as content"),
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as created_at")
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['updates' => $updates]);
    }

    // ── AJAX: mark all read ──────────────────────────────────────────────────

    public function markRead(): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['ok' => false], 401);
        }

        $this->markReadForCustomer();

        return response()->json(['ok' => true]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function markReadForCustomer(): void
    {
        $customerId = Customer::where('user_id', Auth::id())->value('id');
        if (! $customerId) return;

        DB::table('customer_update_reads')->updateOrInsert(
            ['customer_id' => $customerId],
            ['last_seen_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
