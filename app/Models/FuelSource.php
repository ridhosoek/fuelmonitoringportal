<?php

namespace App\Models;

use Database\Factories\FuelSourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelSource extends Model
{
    /** @use HasFactory<FuelSourceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'source_type',
        'address',
        'status',
    ];

    public function receivings(): HasMany
    {
        return $this->hasMany(FuelReceiving::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }
}
