<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBastRequest extends FormRequest
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
            'bast_date' => ['required', 'date'],
            'fuel_dispensing_ids' => ['required', 'array', 'min:1'],
            'fuel_dispensing_ids.*' => ['required', Rule::exists('fuel_dispensings', 'id')],
            'received_by' => ['required', 'string', 'max:255'],
            'operator_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
