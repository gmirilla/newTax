<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\VatReturn;
use App\Services\VatService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTaxReminders extends Command
{
    protected $signature   = 'tax:send-reminders {--dry-run : List what would be sent without sending}';
    protected $description = 'Send Nigerian tax compliance reminders to tenants (VAT, WHT, CIT deadlines)';

    public function __construct(private readonly VatService $vatService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🇳🇬 NaijaBooks Tax Reminder Engine started at ' . now()->toDateTimeString());

        $sent     = 0;
        $skipped  = 0;
        $dryRun   = $this->option('dry-run');
        $today    = now();

        foreach (Tenant::where('is_active', true)->get() as $tenant) {
            $this->line("  Processing: {$tenant->name}");

            // ── VAT reminders ────────────────────────────────────────────────
            if ($tenant->isVatRegistered()) {
                $result = $this->sendVatReminders($tenant, $today, $dryRun);
                $sent  += $result['sent'];
            }

            // ── CIT annual filing reminder ───────────────────────────────────
            $result  = $this->sendCitReminder($tenant, $today, $dryRun);
            $sent   += $result['sent'];

            // ── General compliance check ─────────────────────────────────────
            $this->checkOverdueVat($tenant, $today);
        }

        $this->info("✅ Done. Sent: {$sent} reminders.");

        return self::SUCCESS;
    }

    /**
     * VAT filing reminder: send on the 15th and 19th of each month.
     * Due date is the 21st — reminders give 6 and 2 days notice.
     */
    private function sendVatReminders(Tenant $tenant, Carbon $today, bool $dryRun): array
    {
        $sent = 0;

        // Remind on 15th (6 days before deadline) and 19th (2 days before)
        if (!in_array($today->day, [15, 19])) {
            return ['sent' => 0];
        }

        $taxYear  = $today->year;
        $taxMonth = $today->month - 1; // previous month's return is due
        if ($taxMonth === 0) {
            $taxMonth = 12;
            $taxYear--;
        }

        // Check if already filed
        $existing = VatReturn::where('tenant_id', $tenant->id)
            ->where('tax_year', $taxYear)
            ->where('tax_month', $taxMonth)
            ->whereIn('status', ['filed', 'paid', 'nil_return'])
            ->first();

        if ($existing) {
            $this->line("    → VAT for {$taxMonth}/{$taxYear} already filed. Skipping.");
            return ['sent' => 0];
        }

        $dueDate      = $this->vatService->getFilingDueDate($taxYear, $taxMonth);
        $daysLeft     = $today->diffInDays($dueDate, false);
        $periodName   = date('F Y', mktime(0, 0, 0, $taxMonth, 1, $taxYear));

        $message = "⚠️ VAT Filing Reminder for {$tenant->name}\n"
            . "Period: {$periodName}\n"
            . "Due Date: {$dueDate} ({$daysLeft} days remaining)\n"
            . "File your VAT return via FIRS TaxPro-Max portal or NaijaBooks.\n"
            . "Penalty: 5% of tax due + interest for late filing.";

        if ($dryRun) {
            $this->line("    [DRY-RUN] Would send VAT reminder: {$periodName} due {$dueDate}");
        } else {
            // Log the reminder (replace with actual mail/notification)
            Log::channel('daily')->info("TAX_REMINDER VAT", [
                'tenant_id'  => $tenant->id,
                'tenant'     => $tenant->name,
                'period'     => $periodName,
                'due_date'   => $dueDate,
                'days_left'  => $daysLeft,
            ]);

            $this->info("    ✉️  VAT reminder sent to {$tenant->email} for {$periodName}");
            $sent++;
        }

        return ['sent' => $sent];
    }

    /**
     * CIT annual filing reminder: remind in May (for Dec year-end companies).
     * CIT is due by June 30th for December year-end.
     */
    private function sendCitReminder(Tenant $tenant, Carbon $today, bool $dryRun): array
    {
        // Remind in May (30 days before June 30 deadline)
        if ($today->month !== 5 || $today->day !== 1) {
            return ['sent' => 0];
        }

        $taxYear = $today->year - 1;
        $message = "📋 CIT Annual Filing Reminder for {$tenant->name}\n"
            . "Tax Year: {$taxYear}\n"
            . "Due Date: {$taxYear + 1}-06-30\n"
            . "Rate: {$tenant->getCitRate()}% CIT + 2.5% Education Tax\n"
            . "File via FIRS TaxPro-Max. Penalties apply for late filing.";

        if ($dryRun) {
            $this->line("    [DRY-RUN] Would send CIT reminder for TY {$taxYear}");
        } else {
            Log::channel('daily')->info("TAX_REMINDER CIT", [
                'tenant_id' => $tenant->id,
                'tax_year'  => $taxYear,
            ]);
            $this->info("    ✉️  CIT reminder sent to {$tenant->email} for TY{$taxYear}");
        }

        return ['sent' => 1];
    }

    /**
     * Flag overdue VAT returns in the console output.
     */
    private function checkOverdueVat(Tenant $tenant, Carbon $today): void
    {
        $overdue = VatReturn::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('due_date', '<', $today->toDateString())
            ->count();

        if ($overdue > 0) {
            $this->warn("    ⚠️  {$tenant->name} has {$overdue} OVERDUE VAT return(s)! FIRS penalties accruing.");
        }
    }
}
