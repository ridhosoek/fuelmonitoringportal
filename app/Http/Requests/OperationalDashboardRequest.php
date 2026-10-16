<?php

namespace App\Http\Requests;

use App\Models\Storage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OperationalDashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'site_id' => ['nullable', Rule::exists('sites', 'id')],
            'storage_id' => ['nullable', Rule::exists('storages', 'id')],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('site_id') || ! $this->filled('storage_id')) {
                    return;
                }

                $storageBelongsToSite = Storage::query()
                    ->whereKey($this->integer('storage_id'))
                    ->where('site_id', $this->integer('site_id'))
                    ->exists();

                if (! $storageBelongsToSite) {
                    $validator->errors()->add('storage_id', 'VHS yang dipilih tidak berada pada site tersebut.');
                }
            },
        ];
    }
}
