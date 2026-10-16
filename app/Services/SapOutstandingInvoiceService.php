<?php

namespace App\Services;

use App\Models\OutstandingInvoice;
use Illuminate\Support\Facades\DB;

class SapOutstandingInvoiceService
{
    /**
     * @param  list<array<string, mixed>>  $documents
     */
    public function synchronize(array $documents): int
    {
        return DB::transaction(function () use ($documents): int {
            foreach ($documents as $document) {
                OutstandingInvoice::query()->updateOrCreate(
                    ['sap_document_id' => $document['sap_document_id']],
                    [
                        ...$document,
                        'source_system' => $document['source_system'] ?? 'SAP',
                        'last_synced_at' => now(),
                    ],
                );
            }

            return count($documents);
        });
    }
}
