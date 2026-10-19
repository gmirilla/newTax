<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Transaction;
use Illuminate\Console\Command;

class BackfillExpensePaymentLinks extends Command
{
    protected $signature = 'expenses:backfill-payment-links {--apply : Actually write the links instead of just reporting}';

    protected $description = 'Link paid expenses that predate payment_transaction_id to their already-posted settlement transaction, by matching the legacy PAY-{reference} journal reference';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $candidates = Expense::withoutGlobalScope('tenant')
            ->where('status', 'paid')
            ->whereNull('payment_transaction_id')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No paid expenses are missing a payment_transaction_id link — nothing to do.');
            return self::SUCCESS;
        }

        $rows      = [];
        $linked    = 0;
        $unmatched = 0;
        $mismatched = 0;

        foreach ($candidates as $expense) {
            $legacyReference = 'PAY-' . $expense->reference;

            $transaction = Transaction::where('tenant_id', $expense->tenant_id)
                ->where('type', 'payment')
                ->where('reference', $legacyReference)
                ->first();

            if (! $transaction) {
                $rows[] = [$expense->tenant_id, $expense->reference, '—', 'NO MATCH FOUND'];
                $unmatched++;
                continue;
            }

            $expectedAmount = round((float) $expense->net_payable, 2);
            $foundAmount    = round((float) $transaction->amount, 2);

            if (abs($expectedAmount - $foundAmount) > 0.01) {
                $rows[] = [
                    $expense->tenant_id,
                    $expense->reference,
                    $transaction->reference,
                    "AMOUNT MISMATCH (expected ₦{$expectedAmount}, found ₦{$foundAmount}) — skipped",
                ];
                $mismatched++;
                continue;
            }

            $rows[] = [$expense->tenant_id, $expense->reference, $transaction->reference, $apply ? 'LINKED' : 'would link'];

            if ($apply) {
                $expense->update(['payment_transaction_id' => $transaction->id]);
                $linked++;
            }
        }

        $this->table(['Tenant ID', 'Expense Ref', 'Matched Transaction Ref', 'Result'], $rows);
        $this->newLine();

        if (! $apply) {
            $this->comment('Dry run — no changes written. Re-run with --apply to link the matches shown above.');
        } else {
            $this->info("{$linked} expense(s) linked.");
        }

        if ($unmatched > 0) {
            $this->warn("{$unmatched} expense(s) had no matching transaction — these need a manual ledger correction (the 'Reverse Payment' button will keep refusing them, by design, rather than guess).");
        }

        if ($mismatched > 0) {
            $this->warn("{$mismatched} expense(s) found a reference match but the amount didn't reconcile — skipped for safety, needs manual review.");
        }

        return self::SUCCESS;
    }
}
