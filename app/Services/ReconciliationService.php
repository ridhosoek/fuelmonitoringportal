<?php

namespace App\Services;

use App\Models\DailyReconciliation;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReconciliationService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{
     *     opening_stock: float,
     *     total_receiving: float,
     *     total_dispensing: float,
     *     total_adjustment: float,
     *     system_closing_stock: float
     * }
     */
    public function calculate(Storage $storage, string $date): array
    {
        $openingStock = $this->stockService->openingStock($storage, $date);
        $summary = $this->stockService->dailySummary($storage, $date);
        $systemClosingStock = $openingStock + $summary['receiving'] - $summary['dispensing'] + $summary['adjustment'];

        return [
            'opening_stock' => $openingStock,
            'total_receiving' => $summary['receiving'],
            'total_dispensing' => $summary['dispensing'],
            'total_adjustment' => $summary['adjustment'],
            'system_closing_stock' => $systemClosingStock,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, User $user): DailyReconciliation
    {
        return DB::transaction(function () use ($data, $user): DailyReconciliation {
            $storage = Storage::query()->with('site')->findOrFail($data['storage_id']);
            $calculation = $this->calculate($storage, $data['reconciliation_date']);
            $physicalStock = (float) $data['physical_closing_stock'];
            $variance = $physicalStock - $calculation['system_closing_stock'];
            $variancePercentage = $calculation['system_closing_stock'] === 0.0
                ? 0.0
                : ($variance / $calculation['system_closing_stock']) * 100;

            $existing = DailyReconciliation::query()
                ->where('reconciliation_date', $data['reconciliation_date'])
                ->where('storage_id', $storage->id)
                ->first();

            if ($existing && $existing->status !== DailyReconciliation::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'reconciliation_date' => 'Reconciliation yang sudah SUBMITTED/APPROVED tidak dapat ditimpa.',
                ]);
            }

            $reconciliation = DailyReconciliation::updateOrCreate(
                [
                    'reconciliation_date' => $data['reconciliation_date'],
                    'storage_id' => $storage->id,
                ],
                [
                    'site_id' => $storage->site_id,
                    'opening_stock' => number_format($calculation['opening_stock'], 3, '.', ''),
                    'total_receiving' => number_format($calculation['total_receiving'], 3, '.', ''),
                    'total_dispensing' => number_format($calculation['total_dispensing'], 3, '.', ''),
                    'total_adjustment' => number_format($calculation['total_adjustment'], 3, '.', ''),
                    'system_closing_stock' => number_format($calculation['system_closing_stock'], 3, '.', ''),
                    'physical_closing_stock' => number_format($physicalStock, 3, '.', ''),
                    'variance' => number_format($variance, 3, '.', ''),
                    'variance_percentage' => number_format($variancePercentage, 3, '.', ''),
                    'notes' => $data['notes'] ?? null,
                    'status' => DailyReconciliation::STATUS_DRAFT,
                    'prepared_by' => $user->id,
                ],
            );

            $this->auditLogger->log('CREATE', 'Daily Reconciliation', $reconciliation, null, $reconciliation->toArray(), $user, 'REC-'.$reconciliation->reconciliation_date->format('Ymd'));

            return $reconciliation->fresh(['storage.site', 'preparer']);
        });
    }

    public function submit(DailyReconciliation $reconciliation, User $user): DailyReconciliation
    {
        return DB::transaction(function () use ($reconciliation, $user): DailyReconciliation {
            $reconciliation = DailyReconciliation::query()->lockForUpdate()->findOrFail($reconciliation->id);

            if ($reconciliation->status !== DailyReconciliation::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya reconciliation DRAFT yang dapat disubmit.',
                ]);
            }

            $oldValues = $reconciliation->toArray();
            $reconciliation->update([
                'status' => DailyReconciliation::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            $this->auditLogger->log('POST', 'Daily Reconciliation', $reconciliation, $oldValues, $reconciliation->fresh()->toArray(), $user, 'REC-'.$reconciliation->reconciliation_date->format('Ymd'));

            return $reconciliation->fresh(['storage.site', 'preparer']);
        });
    }

    public function approve(DailyReconciliation $reconciliation, User $user): DailyReconciliation
    {
        return DB::transaction(function () use ($reconciliation, $user): DailyReconciliation {
            $reconciliation = DailyReconciliation::query()->lockForUpdate()->findOrFail($reconciliation->id);

            if ($reconciliation->status !== DailyReconciliation::STATUS_SUBMITTED) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya reconciliation SUBMITTED yang dapat diapprove.',
                ]);
            }

            $oldValues = $reconciliation->toArray();
            $reconciliation->update([
                'status' => DailyReconciliation::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->auditLogger->log('APPROVE', 'Daily Reconciliation', $reconciliation, $oldValues, $reconciliation->fresh()->toArray(), $user, 'REC-'.$reconciliation->reconciliation_date->format('Ymd'));

            return $reconciliation->fresh(['storage.site', 'preparer', 'approver']);
        });
    }
}
