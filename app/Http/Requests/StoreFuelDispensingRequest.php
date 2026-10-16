<?php

namespace App\Http\Requests;

use App\Models\FuelOrderItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFuelDispensingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canWriteTransactions() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isBatchSubmission()) {
            return [
                'dispensing_date' => ['required', 'date'],
                'dispensing_time' => ['nullable', 'date_format:H:i'],
                'fuel_order_id' => ['required', Rule::exists('fuel_orders', 'id')],
                'storage_id' => ['required', Rule::exists('storages', 'id')],
                'operator_name' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
                'override_reason' => ['nullable', 'string', 'max:1000'],
                'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
                'submission_action' => ['nullable', Rule::in(['draft', 'post'])],
                'items' => ['required', 'array', 'min:1', 'max:100'],
                'items.*.fuel_order_item_id' => ['required', 'integer', 'distinct', Rule::exists('fuel_order_items', 'id')],
                'items.*.previous_meter_value' => ['nullable', 'numeric', 'min:0'],
                'items.*.meter_value' => ['nullable', 'numeric', 'min:0'],
                'items.*.quantity_liter' => ['nullable', 'numeric', 'min:0.001'],
                'items.*.production_quantity' => ['nullable', 'numeric', 'min:0'],
                'items.*.fuel_truck_id' => ['nullable', Rule::exists('fuel_trucks', 'id')],
            ];
        }

        return [
            'dispensing_date' => ['required', 'date'],
            'dispensing_time' => ['nullable', 'date_format:H:i'],
            'fuel_order_id' => ['required', Rule::exists('fuel_orders', 'id')],
            'fuel_order_item_id' => ['required', Rule::exists('fuel_order_items', 'id')],
            'storage_id' => ['required', Rule::exists('storages', 'id')],
            'unit_id' => ['required', Rule::exists('units', 'id')],
            'delivery_method' => ['required', Rule::in([
                FuelOrderItem::DELIVERY_DIRECT_STORAGE,
                FuelOrderItem::DELIVERY_MOBILE_TANKER,
            ])],
            'fuel_truck_id' => [
                'nullable',
                'required_if:delivery_method,'.FuelOrderItem::DELIVERY_MOBILE_TANKER,
                'prohibited_unless:delivery_method,'.FuelOrderItem::DELIVERY_MOBILE_TANKER,
                Rule::exists('fuel_trucks', 'id'),
            ],
            'previous_meter_value' => ['required', 'numeric', 'min:0'],
            'meter_value' => ['required', 'numeric', 'gte:previous_meter_value'],
            'quantity_liter' => ['required', 'numeric', 'min:0.001'],
            'production_quantity' => ['nullable', 'numeric', 'min:0'],
            'operator_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'submission_action' => ['nullable', Rule::in(['draft', 'post'])],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        if (! $this->isBatchSubmission()) {
            return [];
        }

        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['fuel_order_id', 'items', 'items.*.fuel_order_item_id'])) {
                    return;
                }

                $submittedItems = collect($this->input('items', []));
                $touchedItems = $submittedItems->filter(
                    fn (array $item): bool => collect([
                        $item['quantity_liter'] ?? null,
                        $item['meter_value'] ?? null,
                        $item['production_quantity'] ?? null,
                        $item['fuel_truck_id'] ?? null,
                    ])->contains(fn (mixed $value): bool => filled($value)),
                );

                if ($touchedItems->isEmpty()) {
                    $validator->errors()->add('items', 'Isi Qty Real minimal pada satu unit yang akan diproses.');

                    return;
                }

                $orderItems = FuelOrderItem::query()
                    ->with('unit')
                    ->whereIn('id', $touchedItems->pluck('fuel_order_item_id')->filter())
                    ->get()
                    ->keyBy('id');
                $fuelOrderId = $this->integer('fuel_order_id');

                foreach ($touchedItems as $index => $submittedItem) {
                    $orderItem = $orderItems->get((int) ($submittedItem['fuel_order_item_id'] ?? 0));

                    if ($orderItem === null || (int) $orderItem->fuel_order_id !== $fuelOrderId) {
                        $validator->errors()->add("items.$index.fuel_order_item_id", 'Item tidak termasuk dalam Fuel Order yang dipilih.');

                        continue;
                    }

                    if ($orderItem->status !== 'ACTIVE' || $orderItem->unit?->status !== 'ACTIVE') {
                        $validator->errors()->add("items.$index.fuel_order_item_id", 'Item dan unit Fuel Order harus aktif.');
                    }

                    if (! filled($submittedItem['quantity_liter'] ?? null)) {
                        $validator->errors()->add("items.$index.quantity_liter", 'Qty Real wajib diisi untuk unit yang diproses.');
                    }

                    if (! filled($submittedItem['previous_meter_value'] ?? null)) {
                        $validator->errors()->add("items.$index.previous_meter_value", 'HM/KM awal wajib diisi untuk unit yang diproses.');
                    }

                    if (! filled($submittedItem['meter_value'] ?? null)) {
                        $validator->errors()->add("items.$index.meter_value", 'HM/KM akhir wajib diisi untuk unit yang diproses.');
                    }

                    if (filled($submittedItem['previous_meter_value'] ?? null)
                        && filled($submittedItem['meter_value'] ?? null)
                        && (float) $submittedItem['meter_value'] < (float) $submittedItem['previous_meter_value']) {
                        $validator->errors()->add("items.$index.meter_value", 'HM/KM akhir tidak boleh lebih kecil dari HM/KM awal.');
                    }

                    if ($orderItem->delivery_method === FuelOrderItem::DELIVERY_MOBILE_TANKER
                        && ! filled($submittedItem['fuel_truck_id'] ?? null)) {
                        $validator->errors()->add("items.$index.fuel_truck_id", 'Mobil tangki wajib dipilih untuk unit tidak bergerak.');
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'previous_meter_value.required' => 'HM/KM awal wajib diisi.',
            'meter_value.required' => 'HM/KM akhir wajib diisi.',
            'meter_value.gte' => 'HM/KM akhir tidak boleh lebih kecil dari HM/KM awal.',
            'fuel_truck_id.required_if' => 'Mobil tangki wajib dipilih untuk distribusi ke unit tidak bergerak.',
            'fuel_truck_id.prohibited_unless' => 'Mobil tangki tidak digunakan untuk dispensing langsung di terminal/storage.',
        ];
    }

    private function isBatchSubmission(): bool
    {
        return is_array($this->input('items'));
    }
}
