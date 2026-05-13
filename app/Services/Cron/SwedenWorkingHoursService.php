<?php

declare(strict_types=1);

namespace App\Services\Cron;

use Carbon\Carbon;

/**
 * Mirrors the old PHP helper functions for Sweden's timezone and working hours.
 *
 * Working hours: Monday–Friday, 08:00–18:00 Europe/Stockholm.
 */
class SwedenWorkingHoursService
{
    public const TIMEZONE = 'Europe/Stockholm';
    public const WORK_START = '08:00:00';
    public const WORK_END = '18:00:00';
    public const CRON_TRIGGER = '17:00:00'; // hour window when cron-jobs were run in old system

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    /**
     * Is it currently a Swedish weekday between 08:00 and 18:00?
     */
    public function isWorkingHours(): bool
    {
        $now = $this->now();

        return $now->isWeekday()
            && $now->format('H:i:s') >= self::WORK_START
            && $now->format('H:i:s') < self::WORK_END;
    }

    /**
     * The next Swedish working hour (used when sending outside working hours so the
     * email_delay flag is set and the email can be queued for the next morning).
     */
    public function getNextWorkingHour(): Carbon
    {
        $dt = $this->now();
        $timeStr = $dt->format('H:i:s');

        if ($dt->isWeekday()) {
            if ($timeStr < self::WORK_START) {
                // Today before 08:00 → today at 08:00
                return $dt->setTime(8, 0, 0)->copy();
            }

            if ($timeStr < self::WORK_END) {
                // Currently working hours
                return $dt->copy();
            }
        }

        // After 18:00 or weekend → next Monday (or next day) at 08:00
        $next = $dt->copy()->addDay();
        while (! $next->isWeekday()) {
            $next->addDay();
        }

        return $next->setTime(8, 0, 0);
    }

    /**
     * Count Mon–Fri working days between two dates (exclusive of the end date).
     * Mirrors getWorkingDaysBetween() from the old PHP functions.php.
     */
    public function getWorkingDaysBetween(string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate, self::TIMEZONE)->startOfDay();
        $end = Carbon::parse($endDate, self::TIMEZONE)->startOfDay();

        if ($start->gte($end)) {
            return 0;
        }

        $days = 0;
        $current = $start->copy();

        while ($current->lt($end)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * How many working days have passed since $date?
     */
    public function workingDaysSince(string $date): int
    {
        return $this->getWorkingDaysBetween($date, $this->now()->toDateString());
    }
}
