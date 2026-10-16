<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\User;
use App\Support\QuantityFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function currentStock(Storage|int $storage): float
    {
        $storageId = $storage instanceof Storage ? $storage->id : $storage;

        return (float) StockMovement::query()
            ->where('storage_id', $storageId)
            ->sum('quantity_liter');
    }

    public function openingStock(Storage|int $storage, string $date): float
    {
        $storageId = $storage instanceof Storage ? $storage->id : $storage;

        return (float) StockMovement::query()
            ->where('storage_id', $storageId)
            ->whereDate('movement_date', '<', $date)
            ->sum('quantity_liter');
    }

    /**
     * @return array{receiving: float, dispensing: float, adjustment: float}
     */
    public function dailySummary(Storage|int $storage, string $date): array
    {
        $storageId = $storage instanceof Storage ? $storage->id : $storage;

        $receiving = (float) StockMovement::query()
            ->where('storage_id', $storageId)
            ->whereDate('movement_date', $date)
            ->where('movement_type', StockMovement::TYPE_RECEIVING)
            ->sum('quantity_liter');

        $dispensing = abs((float) StockMovement::query()
            ->where('storage_id', $storageId)
            ->whereDate('movement_date', $date)
            ->where('movement_type', StockMovement::TYPE_DISPENSING)
            ->sum('quantity_liter'));

        $adjustment = (float) StockMovement::query()
            ->where('storage_id', $storageId)
            ->whereDate('movement_date', $date)
            ->whereIn('movement_type', [StockMovement::TYPE_ADJUSTMENT_IN, StockMovement::TYPE_ADJUSTMENT_OUT])
            ->sum('quantity_liter');

        return [
            'receiving' => $receiving,
            'dispensing' => $dispensing,
            'adjustment' => $adjustment,
        ];
    }

    public function ensureAvailable(Storage $storage, float $quantityLiter): void
    {
        if ($this->currentStock($storage) < $quantityLiter) {
            throw ValidationException::withMessages([
                'quantity_liter' => 'Stok VHS tidak cukup untuk transaksi dispensing.',
            ]);
        }
    }

    public function recordMovement(
        int $storageId,
        string $movementType,
        string $referenceType,
        int $referenceId,
        string $referenceNumber,
        float $quantityLiter,
        User $user,
        string $movementDate,
        ?string $movementTime = null,
        ?string $notes = null,
    ): StockMovement {
        $storage = Storage::query()->whereKey($storageId)->lockForUpdate()->firstOrFail();

        $balanceAfter = $this->currentStock($storageId) + $quantityLiter;

        if ($balanceAfter < 0) {
            throw ValidationException::withMessages([
                'quantity_liter' => 'Stok VHS tidak cukup untuk transaksi ini.',
            ]);
        }

        if ($balanceAfter > (float) $storage->capacity_liter) {
            throw ValidationException::withMessages([
                'quantity_liter' => 'Saldo setelah transaksi melebihi kapasitas VHS '.QuantityFormatter::format($storage->capacity_liter).' liter.',
            ]);
        }

        return StockMovement::create([
            'movement_date' => $movementDate,
            'movement_time' => $movementTime ?: Carbon::now()->format('H:i:s'),
            'storage_id' => $storageId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_number' => $referenceNumber,
            'quantity_liter' => $this->formatDecimal($quantityLiter),
            'balance_after' => $this->formatDecimal($balanceAfter),
            'notes' => $notes,
            'created_by' => $user->id,
        ]);
    }

    /**
     * @return list<StockMovement>
     */
    public function reverseReference(string $referenceType, int $referenceId, User $user, string $reason): array
    {
        $movements = StockMovement::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('movement_type', '!=', StockMovement::TYPE_REVERSAL)
            ->oldest('id')
            ->get();

        if ($movements->isEmpty()) {
            throw ValidationException::withMessages([
                'status' => 'Tidak ada stock movement yang dapat direverse.',
            ]);
        }

        return $movements
            ->map(fn (StockMovement $movement): StockMovement => $this->recordMovement(
                $movement->storage_id,
                StockMovement::TYPE_REVERSAL,
                $referenceType,
                $referenceId,
                $movement->reference_number,
                -1 * (float) $movement->quantity_liter,
                $user,
                now()->toDateString(),
                now()->format('H:i:s'),
                $reason,
            ))
            ->all();
    }

    private function formatDecimal(float $value): string
    {
        return number_format($value, 3, '.', '');
    }
}
