<?php

namespace App\Models;

use Database\Factories\StorageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Storage extends Model
{
    /** @use HasFactory<StorageFactory> */
    use HasFactory;

    public const STOCK_STATUS_EMPTY = 'EMPTY';

    public const STOCK_STATUS_LOW = 'LOW';

    public const STOCK_STATUS_OK = 'OK';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'terminal_id',
        'storage_code',
        'storage_name',
        'capacity_liter',
        'minimum_stock_liter',
        'status',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(Terminal::class);
    }

    public function receivings(): HasMany
    {
        return $this->hasMany(FuelReceiving::class);
    }

    public function dispensings(): HasMany
    {
        return $this->hasMany(FuelDispensing::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(DailyReconciliation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    public function isLowStock(float $currentStock): bool
    {
        return $this->stockStatus($currentStock) === self::STOCK_STATUS_LOW;
    }

    public function isEmptyStock(float $currentStock): bool
    {
        return $this->stockStatus($currentStock) === self::STOCK_STATUS_EMPTY;
    }

    public function stockStatus(float $currentStock): string
    {
        if ($currentStock <= 0) {
            return self::STOCK_STATUS_EMPTY;
        }

        if ($currentStock <= (float) $this->minimum_stock_liter) {
            return self::STOCK_STATUS_LOW;
        }

        return self::STOCK_STATUS_OK;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity_liter' => 'decimal:3',
            'minimum_stock_liter' => 'decimal:3',
        ];
    }
}
