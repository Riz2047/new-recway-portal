<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerInvoice extends Model
{
    protected $table = 'customer_invoices';

    protected $fillable = [
        'customer_id',
        'period',
        'invoice_amount',
        'status',
        'candidate_ids',
        'notes',
        'due_date',
        'created_date',
        'sent_at',
    ];

    protected $casts = [
        'invoice_amount' => 'decimal:2',
        'candidate_ids' => 'array',
        'due_date' => 'date',
        'created_date' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_TO_BE_INVOICED = 'to_be_invoiced';
    public const STATUS_SENT = 'sent';

    // Period constants (mirrors customers.invoice_period)
    public const PERIOD_DAY = 'day';
    public const PERIOD_WEEK = 'week';
    public const PERIOD_MONTH = 'month';

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_TO_BE_INVOICED);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeForPeriod(Builder $query, string $period): Builder
    {
        return $query->where('period', $period);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getCandidateCount(): int
    {
        return count($this->candidate_ids ?? []);
    }

    public function getCandidates(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->candidate_ids ?? [];
        if (empty($ids)) {
            return Candidate::query()->whereRaw('1=0')->get();
        }

        return Candidate::with(['serviceType', 'statusRelation', 'staff'])
            ->whereIn('id', $ids)
            ->get();
    }

    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period) {
            self::PERIOD_DAY => __('Daily'),
            self::PERIOD_WEEK => __('Weekly'),
            self::PERIOD_MONTH => __('Monthly'),
            default => ucfirst($this->period),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_TO_BE_INVOICED => __('To Be Invoiced'),
            self::STATUS_SENT => __('Sent'),
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status === self::STATUS_SENT
            ? 'text-green-700 bg-green-100 dark:bg-green-900/40 dark:text-green-300'
            : 'text-yellow-700 bg-yellow-100 dark:bg-yellow-900/40 dark:text-yellow-300';
    }

    public function markAsSent(): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }
}
