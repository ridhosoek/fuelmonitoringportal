<?php

namespace Database\Factories;

use App\Models\OutstandingInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutstandingInvoice>
 */
class OutstandingInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(5_000_000, 500_000_000);

        return [
            'source_system' => 'SAP',
            'sap_document_id' => fake()->unique()->numerify('19########'),
            'company_code' => 'VHS',
            'customer_code' => 'BNCT',
            'customer_name' => 'PT Berlian Nusantara Container Terminal',
            'invoice_number' => fake()->unique()->numerify('INV-2026-####'),
            'document_date' => today()->subDays(45),
            'due_date' => today()->subDays(15),
            'currency' => 'IDR',
            'original_amount' => $amount,
            'outstanding_amount' => $amount,
            'payment_terms' => 'NET30',
            'status' => OutstandingInvoice::STATUS_OVERDUE,
            'last_synced_at' => now(),
        ];
    }
}
