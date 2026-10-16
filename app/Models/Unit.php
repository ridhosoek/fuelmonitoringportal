<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    public const MOBILITY_MOVABLE = 'MOVABLE';

    public const MOBILITY_STATIONARY = 'STATIONARY';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'site_id',
        'unit_code',
        'unit_name',
        'plate_number',
        'unit_type',
        'mobility_type',
        'brand',
        'model',
        'meter_type',
        'production_unit',
        'status',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(FuelOrderItem::class);
    }

    public function dispensings(): HasMany
    {
        return $this->hasMany(FuelDispensing::class);
    }

    public function bastItems(): HasMany
    {
        return $this->hasMany(BastItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }
}
