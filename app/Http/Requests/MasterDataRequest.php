<?php

namespace App\Http\Requests;

use App\Models\Terminal;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MasterDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole([User::ROLE_SUPER_ADMIN, User::ROLE_VHS_ADMIN]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $resource = (string) $this->route('resource');
        $id = $this->route('id');
        $status = ['required', Rule::in(['ACTIVE', 'INACTIVE'])];

        return match ($resource) {
            'customers' => [
                'customer_code' => ['required', 'string', 'max:50', Rule::unique('customers', 'customer_code')->ignore($id)],
                'customer_name' => ['required', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'status' => $status,
            ],
            'sites' => [
                'customer_id' => ['required', Rule::exists('customers', 'id')],
                'site_code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('sites', 'site_code')
                        ->where(fn ($query) => $query->where('customer_id', $this->input('customer_id')))
                        ->ignore($id),
                ],
                'site_name' => ['required', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
                'status' => $status,
            ],
            'storages' => [
                'site_id' => ['required', Rule::exists('sites', 'id')],
                'terminal_id' => ['required', Rule::exists('terminals', 'id')],
                'storage_code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('storages', 'storage_code')
                        ->where(fn ($query) => $query->where('site_id', $this->input('site_id')))
                        ->ignore($id),
                ],
                'storage_name' => ['required', 'string', 'max:255'],
                'capacity_liter' => ['required', 'numeric', 'min:0.001'],
                'minimum_stock_liter' => ['required', 'numeric', 'min:0', 'lte:capacity_liter'],
                'status' => $status,
            ],
            'terminals' => [
                'site_id' => ['required', Rule::exists('sites', 'id')],
                'terminal_code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('terminals', 'terminal_code')
                        ->where(fn ($query) => $query->where('site_id', $this->input('site_id')))
                        ->ignore($id),
                ],
                'terminal_name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => $status,
            ],
            'fuel-trucks' => [
                'truck_code' => ['required', 'string', 'max:50', Rule::unique('fuel_trucks', 'truck_code')->ignore($id)],
                'plate_number' => ['required', 'string', 'max:50', Rule::unique('fuel_trucks', 'plate_number')->ignore($id)],
                'capacity_liter' => ['required', 'numeric', 'min:0.001'],
                'driver_name' => ['nullable', 'string', 'max:255'],
                'status' => $status,
            ],
            'fuel-sources' => [
                'code' => ['required', 'string', 'max:50', Rule::unique('fuel_sources', 'code')->ignore($id)],
                'name' => ['required', 'string', 'max:255'],
                'source_type' => ['required', 'string', 'max:100'],
                'address' => ['nullable', 'string'],
                'status' => $status,
            ],
            'units' => [
                'customer_id' => ['required', Rule::exists('customers', 'id')],
                'site_id' => ['required', Rule::exists('sites', 'id')],
                'unit_code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('units', 'unit_code')
                        ->where(fn ($query) => $query->where('site_id', $this->input('site_id')))
                        ->ignore($id),
                ],
                'unit_name' => ['required', 'string', 'max:255'],
                'plate_number' => ['nullable', 'string', 'max:50'],
                'unit_type' => ['required', 'string', 'max:100'],
                'mobility_type' => ['required', Rule::in(['MOVABLE', 'STATIONARY'])],
                'brand' => ['nullable', 'string', 'max:100'],
                'model' => ['nullable', 'string', 'max:100'],
                'meter_type' => ['required', Rule::in(['KM', 'HM'])],
                'production_unit' => ['nullable', 'string', 'max:30'],
                'status' => $status,
            ],
            'users' => [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($id)],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
                'role' => ['required', Rule::in(array_keys(User::roleLabels()))],
                'is_active' => ['required', 'boolean'],
                'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            ],
            default => abort(404),
        };
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'minimum_stock_liter.lte' => 'Batas minimum stok tidak boleh melebihi kapasitas VHS.',
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->route('resource') !== 'storages' || $validator->errors()->hasAny(['site_id', 'terminal_id'])) {
                    return;
                }

                $terminal = Terminal::query()->find($this->integer('terminal_id'));

                if ($terminal !== null && $terminal->site_id !== $this->integer('site_id')) {
                    $validator->errors()->add('terminal_id', 'Terminal harus berada pada site yang sama dengan VHS.');
                }
            },
        ];
    }
}
