<?php

namespace App\Models;

use Database\Factories\DailyReconciliationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReconciliation extends Model
{
    /** @use HasFactory<DailyReconciliationFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_APPROVED = 'APPROVED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reconciliation_date',
        'site_id',
        'storage_id',
        'opening_stock',
        'total_receiving',
        'total_dispensing',
        'total_adjustment',
        'system_closing_stock',
        'physical_closing_stock',
        'variance',
        'variance_percentage',
        'notes',
        'status',
        'prepared_by',
        'submitted_at',
        'approved_by',
        'approved_at',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reconciliation_date' => 'date',
            'opening_stock' => 'decimal:3',
            'total_receiving' => 'decimal:3',
            'total_dispensing' => 'decimal:3',
            'total_adjustment' => 'decimal:3',
            'system_closing_stock' => 'decimal:3',
            'physical_closing_stock' => 'decimal:3',
            'variance' => 'decimal:3',
            'variance_percentage' => 'decimal:3',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }
}
