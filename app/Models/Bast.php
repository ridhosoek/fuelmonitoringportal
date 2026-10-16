<?php

namespace App\Models;

use Database\Factories\BastFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bast extends Model
{
    /** @use HasFactory<BastFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_FINAL = 'FINAL';

    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bast_number',
        'bast_date',
        'fuel_order_id',
        'customer_id',
        'site_id',
        'received_by',
        'operator_name',
        'quantity_liter',
        'notes',
        'status',
        'created_by',
        'finalized_by',
        'finalized_at',
    ];

    public function fuelOrder(): BelongsTo
    {
        return $this->belongsTo(FuelOrder::class);
    }

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
        return $this->hasMany(BastItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bast_date' => 'date',
            'quantity_liter' => 'decimal:3',
            'finalized_at' => 'datetime',
        ];
    }
}
