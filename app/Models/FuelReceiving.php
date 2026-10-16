<?php

namespace App\Models;

use Database\Factories\FuelReceivingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelReceiving extends Model
{
    /** @use HasFactory<FuelReceivingFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_POSTED = 'POSTED';

    public const STATUS_REVERSED = 'REVERSED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'receiving_number',
        'receiving_date',
        'receiving_time',
        'site_id',
        'storage_id',
        'fuel_source_id',
        'delivery_document_number',
        'truck_plate_number',
        'driver_name',
        'quantity_liter',
        'notes',
        'attachment_path',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    public function fuelSource(): BelongsTo
    {
        return $this->belongsTo(FuelSource::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'receiving_date' => 'date',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
            'quantity_liter' => 'decimal:3',
        ];
    }
}
