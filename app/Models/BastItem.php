<?php

namespace App\Models;

use Database\Factories\BastItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BastItem extends Model
{
    /** @use HasFactory<BastItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bast_id',
        'fuel_dispensing_id',
        'unit_id',
        'quantity_liter',
        'meter_type',
        'meter_value',
    ];

    public function bast(): BelongsTo
    {
        return $this->belongsTo(Bast::class);
    }

    public function fuelDispensing(): BelongsTo
    {
        return $this->belongsTo(FuelDispensing::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_liter' => 'decimal:3',
            'meter_value' => 'decimal:3',
        ];
    }
}
