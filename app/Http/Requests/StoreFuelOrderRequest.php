<?php

namespace App\Http\Requests;

use App\Models\FuelOrder;
use App\Models\FuelOrderItem;
use App\Models\Site;
use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFuelOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canCreateFuelOrders() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fo_date' => ['required', 'date'],
            'requested_time' => ['nullable', 'date_format:H:i'],
            'planned_delivery_date' => ['required', 'date', 'after:fo_date'],
            'customer_id' => ['required', Rule::exists('customers', 'id')],
            'site_id' => ['required', Rule::exists('sites', 'id')],
            'requested_by' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in([FuelOrder::STATUS_DRAFT, FuelOrder::STATUS_OPEN])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.unit_id' => ['required', 'distinct', Rule::exists('units', 'id')],
            'items.*.delivery_method' => ['required', Rule::in([
                FuelOrderItem::DELIVERY_DIRECT_STORAGE,
                FuelOrderItem::DELIVERY_MOBILE_TANKER,
            ])],
            'items.*.requested_quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('items')) {
                    return;
                }

                foreach ($this->input('items', []) as $index => $item) {
                    $unit = Unit::query()->find($item['unit_id'] ?? null);
                    $method = $item['delivery_method'] ?? null;

                    if ($unit === null) {
                        continue;
                    }

                    if ($unit->site_id !== $this->integer('site_id') || $unit->customer_id !== $this->integer('customer_id')) {
                        $validator->errors()->add("items.$index.unit_id", 'Unit harus berada pada customer dan site Fuel Order yang dipilih.');
                    }

                    if ($method === FuelOrderItem::DELIVERY_DIRECT_STORAGE && $unit->mobility_type !== Unit::MOBILITY_MOVABLE) {
                        $validator->errors()->add("items.$index.delivery_method", 'Unit tidak bergerak harus dilayani menggunakan mobil tangki.');
                    }

                    if ($method === FuelOrderItem::DELIVERY_MOBILE_TANKER && $unit->mobility_type !== Unit::MOBILITY_STATIONARY) {
                        $validator->errors()->add("items.$index.delivery_method", 'Unit bergerak harus melakukan dispensing langsung di terminal/storage.');
                    }
                }

                $site = Site::query()->find($this->integer('site_id'));

                if ($site !== null && $site->customer_id !== $this->integer('customer_id')) {
                    $validator->errors()->add('site_id', 'Site harus dimiliki oleh customer yang dipilih.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'planned_delivery_date.after' => 'Tanggal rencana pengisian harus setelah tanggal permintaan.',
        ];
    }
}
