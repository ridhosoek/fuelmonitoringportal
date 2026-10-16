<?php

namespace App\Models;

use Database\Factories\FuelTruckFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelTruck extends Model
{
    /** @use HasFactory<FuelTruckFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'truck_code',
        'plate_number',
        'capacity_liter',
        'driver_name',
        'status',
    ];

    public function dispensings(): HasMany
    {
        return $this->hasMany(FuelDispensing::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'capacity_liter' => 'decimal:3',
        ];
    }
}
