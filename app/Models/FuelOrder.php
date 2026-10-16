<?php

namespace App\Models;

use Database\Factories\FuelOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelOrder extends Model
{
    /** @use HasFactory<FuelOrderFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_PARTIAL = 'PARTIAL';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fo_number',
        'fo_date',
        'requested_time',
        'planned_delivery_date',
        'customer_id',
        'site_id',
        'requested_by',
        'notes',
        'status',
        'created_by',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FuelOrderItem::class);
    }

    public function dispensings(): HasMany
    {
        return $this->hasMany(FuelDispensing::class);
    }

    public function basts(): HasMany
    {
        return $this->hasMany(Bast::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActiveForDispensing(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_PARTIAL]);
    }

    public function totalRequested(): float
    {
        return (float) $this->items->sum('requested_quantity');
    }

    public function totalDispensed(): float
    {
        return (float) $this->dispensings
            ->where('status', FuelDispensing::STATUS_POSTED)
            ->sum('quantity_liter');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fo_date' => 'date',
            'planned_delivery_date' => 'date',
        ];
    }
}
