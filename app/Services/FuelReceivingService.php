<?php

namespace App\Services;

use App\Models\FuelReceiving;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FuelReceivingService
{
    public function __construct(
        private readonly NumberGenerator $numberGenerator,
        private readonly StockService $stockService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, User $user): FuelReceiving
    {
        return DB::transaction(function () use ($data, $user): FuelReceiving {
            $receiving = FuelReceiving::create([
                ...$data,
                'receiving_number' => $data['receiving_number'] ?? $this->numberGenerator->next('RCV', FuelReceiving::class, 'receiving_number', $data['receiving_date'] ?? null),
                'status' => $data['status'] ?? FuelReceiving::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            $this->auditLogger->log('CREATE', 'Fuel Receiving', $receiving, null, $receiving->toArray(), $user, $receiving->receiving_number);

            return $receiving;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAndPost(array $data, User $user): FuelReceiving
    {
        return DB::transaction(function () use ($data, $user): FuelReceiving {
            $receiving = $this->createDraft($data, $user);

            return $this->post($receiving, $user);
        });
    }

    public function post(FuelReceiving $receiving, User $user): FuelReceiving
    {
        return DB::transaction(function () use ($receiving, $user): FuelReceiving {
            $receiving = FuelReceiving::query()->lockForUpdate()->findOrFail($receiving->id);

            if ($receiving->status !== FuelReceiving::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya receiving DRAFT yang dapat diposting.',
                ]);
            }

            $oldValues = $receiving->toArray();
            $receiving->update([
                'status' => FuelReceiving::STATUS_POSTED,
                'posted_by' => $user->id,
                'posted_at' => now(),
            ]);

            $this->stockService->recordMovement(
                $receiving->storage_id,
                StockMovement::TYPE_RECEIVING,
                'fuel_receiving',
                $receiving->id,
                $receiving->receiving_number,
                (float) $receiving->quantity_liter,
                $user,
                $receiving->receiving_date->toDateString(),
                $receiving->receiving_time,
                'Posted fuel receiving',
            );

            $this->auditLogger->log('POST', 'Fuel Receiving', $receiving, $oldValues, $receiving->fresh()->toArray(), $user, $receiving->receiving_number);

            return $receiving->fresh(['storage', 'fuelSource', 'site']);
        });
    }

    public function reverse(FuelReceiving $receiving, User $user, string $reason): FuelReceiving
    {
        return DB::transaction(function () use ($receiving, $user, $reason): FuelReceiving {
            $receiving = FuelReceiving::query()->lockForUpdate()->findOrFail($receiving->id);

            if ($receiving->status !== FuelReceiving::STATUS_POSTED) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya receiving POSTED yang dapat direverse.',
                ]);
            }

            $oldValues = $receiving->toArray();

            $this->stockService->reverseReference('fuel_receiving', $receiving->id, $user, $reason);

            $receiving->update([
                'status' => FuelReceiving::STATUS_REVERSED,
                'reversed_by' => $user->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            $this->auditLogger->log('REVERSE', 'Fuel Receiving', $receiving, $oldValues, $receiving->fresh()->toArray(), $user, $receiving->receiving_number);

            return $receiving->fresh(['storage', 'fuelSource', 'site']);
        });
    }
}
