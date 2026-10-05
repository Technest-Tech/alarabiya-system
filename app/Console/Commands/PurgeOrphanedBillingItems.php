<?php

namespace App\Console\Commands;

use App\Models\Billing;
use App\Models\BillingItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeOrphanedBillingItems extends Command
{
    protected $signature = 'billing:purge-orphaned-items
                            {--dry-run : Only report what would change}
                            {--force : Apply without asking for confirmation}';

    protected $description = 'Remove billing items left behind by deleted lessons from unpaid automatic billings';

    /**
     * Until the LessonObserver fix, deleting a lesson left its billing item
     * in place with lesson_id = NULL (FK ON DELETE SET NULL), so the student
     * kept being charged. Automatic billings only ever hold lesson items, so a
     * NULL lesson_id there is always such a leftover. Paid billings are only
     * reported: refunding or crediting them is a business decision.
     */
    public function handle(): int
    {
        $orphans = BillingItem::query()
            ->join('billings', 'billings.id', '=', 'billing_items.billing_id')
            ->whereNull('billing_items.lesson_id')
            ->where('billings.type', 'automatic')
            ->select('billing_items.*', 'billings.student_id', 'billings.month', 'billings.status as billing_status', 'billings.currency', 'billings.total_amount as billing_total')
            ->orderBy('billing_items.id')
            ->get();

        $unpaid = $orphans->where('billing_status', '!=', 'paid');
        $paid = $orphans->where('billing_status', 'paid');

        $this->info(sprintf(
            'Orphaned items: %d on unpaid billings (%d billings), %d on paid billings (%d billings).',
            $unpaid->count(), $unpaid->unique('billing_id')->count(),
            $paid->count(), $paid->unique('billing_id')->count()
        ));

        $this->reportByCurrency('Unpaid — will be removed', $unpaid);
        $this->reportPaid($paid);

        if ($unpaid->isEmpty()) {
            $this->info('Nothing to remove.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run: no changes made.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Remove {$unpaid->count()} orphaned items from unpaid billings?")) {
            return self::FAILURE;
        }

        $backupPath = 'backups/orphaned-billing-items-'.now()->format('Ymd_His').'.json';
        Storage::disk('local')->put($backupPath, json_encode([
            'created_at' => now()->toIso8601String(),
            'removed_items' => $unpaid->values()->map->getAttributes(),
            'paid_items_not_touched' => $paid->values()->map->getAttributes(),
        ], JSON_PRETTY_PRINT));
        $this->info('Backup written to '.Storage::disk('local')->path($backupPath));

        $removedBillings = 0;
        DB::transaction(function () use ($unpaid, &$removedBillings) {
            BillingItem::whereIn('id', $unpaid->pluck('id'))->delete();

            foreach (Billing::whereIn('id', $unpaid->pluck('billing_id')->unique())->get() as $billing) {
                // Same rule as BillingService: an automatic billing with no
                // items left has nothing to bill.
                if ($billing->items()->count() === 0) {
                    $billing->delete();
                    $removedBillings++;
                    continue;
                }

                $billing->update(['total_amount' => $billing->items()->sum('amount')]);
            }
        });

        $this->info(sprintf(
            'Removed %d items; recalculated %d billings (%d now empty and deleted).',
            $unpaid->count(), $unpaid->unique('billing_id')->count(), $removedBillings
        ));

        return self::SUCCESS;
    }

    private function reportByCurrency(string $title, $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $this->line($title.':');
        $this->table(
            ['Currency', 'Items', 'Billings', 'Students', 'Amount'],
            $items->groupBy('currency')->map(fn ($group, $currency) => [
                $currency ?: '-',
                $group->count(),
                $group->unique('billing_id')->count(),
                $group->unique('student_id')->count(),
                number_format($group->sum('amount'), 2),
            ])->values()
        );
    }

    private function reportPaid($items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $this->line('Paid billings with orphaned items (NOT changed — review for refund/credit):');
        $this->table(
            ['Billing', 'Student', 'Month', 'Currency', 'Billed total', 'Overcharge', 'Correct total'],
            $items->groupBy('billing_id')->map(function ($group, $billingId) {
                $first = $group->first();
                $overcharge = $group->sum('amount');

                return [
                    $billingId,
                    $first->student_id,
                    substr((string) $first->month, 0, 7),
                    $first->currency,
                    number_format($first->billing_total, 2),
                    number_format($overcharge, 2),
                    number_format($first->billing_total - $overcharge, 2),
                ];
            })->values()
        );
    }
}
