<?php

namespace App\Models;

use Database\Factories\FuelOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelOrderItem extends Model
{
    /** @use HasFactory<FuelOrderItemFactory> */
    use HasFactory;

    public const DELIVERY_DIRECT_STORAGE = 'DIRECT_STORAGE';

    public const DELIVERY_MOBILE_TANKER = 'MOBILE_TANKER';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fuel_order_id',
        'unit_id',
        'delivery_method',
        'requested_quantity',
        'notes',
        'status',
    ];

    public function fuelOrder(): BelongsTo
    {
        return $this->belongsTo(FuelOrder::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function dispensings(): HasMany
    {
        return $this->hasMany(FuelDispensing::class);
    }

    public function dispensedQuantity(): float
    {
        return (float) $this->dispensings()
            ->where('status', FuelDispensing::STATUS_POSTED)
            ->sum('quantity_liter');
    }

    public function remainingQuantity(): float
    {
        return max(0, (float) $this->requested_quantity - $this->dispensedQuantity());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:3',
        ];
    }
}
