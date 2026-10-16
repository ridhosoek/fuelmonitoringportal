<?php

namespace App\Models;

use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public const TYPE_RECEIVING = 'RECEIVING';

    public const TYPE_DISPENSING = 'DISPENSING';

    public const TYPE_ADJUSTMENT_IN = 'ADJUSTMENT_IN';

    public const TYPE_ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';

    public const TYPE_REVERSAL = 'REVERSAL';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movement_date',
        'movement_time',
        'storage_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'reference_number',
        'quantity_liter',
        'balance_after',
        'notes',
        'created_by',
    ];

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'quantity_liter' => 'decimal:3',
            'balance_after' => 'decimal:3',
        ];
    }
}
