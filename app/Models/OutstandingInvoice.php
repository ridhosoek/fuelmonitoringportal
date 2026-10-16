<?php

namespace App\Models;

use Database\Factories\OutstandingInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OutstandingInvoice extends Model
{
    /** @use HasFactory<OutstandingInvoiceFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_PARTIAL = 'PARTIAL';

    public const STATUS_OVERDUE = 'OVERDUE';

    public const STATUS_PAID = 'PAID';

    public const STATUS_DISPUTED = 'DISPUTED';

    /** @var list<string> */
    protected $fillable = [
        'source_system',
        'sap_document_id',
        'company_code',
        'customer_code',
        'customer_name',
        'invoice_number',
        'document_date',
        'due_date',
        'currency',
        'original_amount',
        'outstanding_amount',
        'payment_terms',
        'status',
        'last_synced_at',
        'source_payload',
    ];

    public function agingDays(?Carbon $asOf = null): int
    {
        $asOf ??= now();

        return $this->due_date->isPast() ? $this->due_date->diffInDays($asOf) : 0;
    }

    public function operationalStatus(): string
    {
        if ((float) $this->outstanding_amount <= 0) {
            return self::STATUS_PAID;
        }

        if ($this->due_date->isPast() && ! in_array($this->status, [self::STATUS_DISPUTED, self::STATUS_PAID], true)) {
            return self::STATUS_OVERDUE;
        }

        return $this->status;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'due_date' => 'date',
            'original_amount' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'source_payload' => 'array',
        ];
    }
}
