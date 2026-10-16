<?php

namespace App\Models;

use Database\Factories\FuelDispensingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FuelDispensing extends Model
{
    /** @use HasFactory<FuelDispensingFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_POSTED = 'POSTED';

    public const STATUS_REVERSED = 'REVERSED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dispensing_number',
        'dispensing_date',
        'dispensing_time',
        'fuel_order_id',
        'fuel_order_item_id',
        'storage_id',
        'unit_id',
        'delivery_method',
        'fuel_truck_id',
        'meter_type',
        'previous_meter_value',
        'meter_value',
        'quantity_liter',
        'production_quantity',
        'production_unit',
        'operator_name',
        'notes',
        'attachment_path',
        'status',
        'override_reason',
        'created_by',
        'posted_by',
        'posted_at',
        'approved_by',
        'approved_at',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
    ];

    public function fuelOrder(): BelongsTo
    {
        return $this->belongsTo(FuelOrder::class);
    }

    public function fuelOrderItem(): BelongsTo
    {
        return $this->belongsTo(FuelOrderItem::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function fuelTruck(): BelongsTo
    {
        return $this->belongsTo(FuelTruck::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function bastItem(): HasOne
    {
        return $this->hasOne(BastItem::class);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function workingHours(): ?float
    {
        if ($this->previous_meter_value === null || $this->meter_value === null) {
            return null;
        }

        return max(0, (float) $this->meter_value - (float) $this->previous_meter_value);
    }

    public function literPerHour(): ?float
    {
        $workingHours = $this->workingHours();

        return $workingHours > 0 ? (float) $this->quantity_liter / $workingHours : null;
    }

    public function literPerProductionUnit(): ?float
    {
        $production = (float) $this->production_quantity;

        return $production > 0 ? (float) $this->quantity_liter / $production : null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dispensing_date' => 'date',
            'posted_at' => 'datetime',
            'approved_at' => 'datetime',
            'reversed_at' => 'datetime',
            'previous_meter_value' => 'decimal:3',
            'meter_value' => 'decimal:3',
            'quantity_liter' => 'decimal:3',
            'production_quantity' => 'decimal:3',
        ];
    }
}
