<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFuelReceivingRequest extends FormRequest
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
        return [
            'receiving_date' => ['required', 'date'],
            'receiving_time' => ['nullable', 'date_format:H:i'],
            'site_id' => ['required', Rule::exists('sites', 'id')],
            'storage_id' => ['required', Rule::exists('storages', 'id')],
            'fuel_source_id' => ['required', Rule::exists('fuel_sources', 'id')],
            'delivery_document_number' => ['nullable', 'string', 'max:100'],
            'truck_plate_number' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'quantity_liter' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'submission_action' => ['nullable', Rule::in(['draft', 'post'])],
        ];
    }
}
