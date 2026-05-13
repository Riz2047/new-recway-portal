<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OtpVerification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors expiredOTP() from the old system.
 * Deletes OTP verification records older than 24 hours.
 *
 * Schedule: daily at 00:05.
 */
class CleanupOtpCommand extends Command
{
    protected $signature = 'cleanup:otp';

    protected $description = 'Delete expired OTP verification records (> 24 hours old)';

    public function handle(): int
    {
        if (! Schema::hasTable('otp_verification')) {
            $this->line('otp_verification table does not exist — skipping.');
            return self::SUCCESS;
        }

        $deleted = OtpVerification::where('date_time', '<', now()->subHours(OtpVerification::TTL_HOURS))
            ->delete();

        $this->info("Deleted {$deleted} expired OTP record(s).");

        return self::SUCCESS;
    }
}
