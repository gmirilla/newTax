<?php

namespace App\Console\Commands;

use App\Services\ReferralService;
use Illuminate\Console\Command;

class ReleaseStaleReferralCredit extends Command
{
    protected $signature = 'referrals:release-stale-credit {--hours=24 : Age after which an unpaid checkout debit is refunded}';
    protected $description = 'Refund referral credit that was reserved at checkout but never paid for (abandoned checkouts)';

    public function handle(ReferralService $referrals): int
    {
        $released = $referrals->releaseStaleCredit((int) $this->option('hours'));

        $this->info("Released {$released} stale referral credit reservation(s).");

        return self::SUCCESS;
    }
}
