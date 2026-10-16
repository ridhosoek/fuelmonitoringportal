<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterDataRequest;
use App\Models\Customer;
use App\Models\FuelSource;
use App\Models\FuelTruck;
use App\Models\Site;
use App\Models\Storage;
use App\Models\Terminal;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TabularPdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'resource' => ['nullable', Rule::in(array_keys($this->resources()))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $resource = $filters['resource'] ?? 'customers';
        $query = $this->resourceQuery($resource);

        if (filled($filters['q'] ?? null)) {
            $search = $filters['q'];
            $query->where(function (Builder $builder) use ($resource, $search): void {
                foreach ($this->searchColumns($resource) as $index => $column) {
                    $index === 0
                        ? $builder->where($column, 'like', '%'.$search.'%')
                        : $builder->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        $cards = $query
            ->paginate(18)
            ->withQueryString()
            ->through(fn (Model $record): array => [
                'record' => $record,
                ...$this->cardData($resource, $record),
            ]);

        return view('master.index', [
            'resources' => $this->resources(),
            'resource' => $resource,
            'module' => $this->resources()[$resource],
            'cards' => $cards,
            'filters' => $filters,
        ]);
    }

    public function create(string $resource): View
    {
        $this->modelClass($resource);

        return view('master.form', [
            'resource' => $resource,
            'module' => $this->resources()[$resource],
            'record' => null,
            ...$this->formLookups(),
        ]);
    }

    public function edit(string $resource, int $id): View
    {
        $modelClass = $this->modelClass($resource);

        return view('master.form', [
            'resource' => $resource,
            'module' => $this->resources()[$resource],
            'record' => $modelClass::query()->findOrFail($id),
            ...$this->formLookups(),
        ]);
    }

    public function store(MasterDataRequest $request, string $resource, AuditLogger $auditLogger): RedirectResponse
    {
        $modelClass = $this->modelClass($resource);
        $model = $modelClass::query()->create($this->normalizedData($request));
        $auditLogger->log('CREATE', $this->moduleName($resource), $model, null, $model->toArray(), $request->user(), $this->reference($model));

        return redirect()->route('master.index', ['resource' => $resource])->with('status', $this->moduleName($resource).' berhasil dibuat.');
    }

    public function update(MasterDataRequest $request, string $resource, int $id, AuditLogger $auditLogger): RedirectResponse
    {
        $modelClass = $this->modelClass($resource);
        $model = $modelClass::query()->findOrFail($id);
        $oldValues = $model->toArray();
        $model->update($this->normalizedData($request));
        $auditLogger->log('UPDATE', $this->moduleName($resource), $model, $oldValues, $model->fresh()->toArray(), $request->user(), $this->reference($model));

        return redirect()->route('master.index', ['resource' => $resource])->with('status', $this->moduleName($resource).' berhasil diperbarui.');
    }

    public function exportPdf(string $resource, TabularPdfService $pdf): Response
    {
        $module = $this->resources()[$resource] ?? abort(404);
        [$headers, $rows] = $this->pdfRows($resource);
        $binary = $pdf->render(
            'MASTER DATA '.strtoupper($module['plural']),
            'Data aktif dan nonaktif per '.now()->format('d-m-Y H:i'),
            $headers,
            $rows,
            ['Total Data' => count($rows)],
        );

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="master-'.$resource.'-'.now()->format('Ymd-His').'.pdf"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /** @return array<string, array{singular: string, plural: string, icon: string}> */
    private function resources(): array
    {
        return [
            'customers' => ['singular' => 'Customer', 'plural' => 'Customers', 'icon' => 'bi-buildings'],
            'sites' => ['singular' => 'Site', 'plural' => 'Sites', 'icon' => 'bi-geo-alt'],
            'terminals' => ['singular' => 'Terminal', 'plural' => 'Terminals', 'icon' => 'bi-grid-1x2'],
            'storages' => ['singular' => 'VHS / Tangki', 'plural' => 'VHS / Tangki', 'icon' => 'bi-database'],
            'fuel-trucks' => ['singular' => 'Mobil Tangki', 'plural' => 'Mobil Tangki', 'icon' => 'bi-truck'],
            'fuel-sources' => ['singular' => 'Sumber BBM', 'plural' => 'Sumber BBM', 'icon' => 'bi-fuel-pump'],
            'units' => ['singular' => 'Unit', 'plural' => 'Units', 'icon' => 'bi-box-seam'],
            'users' => ['singular' => 'User', 'plural' => 'Users', 'icon' => 'bi-people'],
        ];
    }

    private function resourceQuery(string $resource): Builder
    {
        return match ($resource) {
            'customers' => Customer::query()->orderBy('customer_name'),
            'sites' => Site::query()->with('customer')->orderBy('site_name'),
            'terminals' => Terminal::query()->with(['site', 'storages'])->orderBy('terminal_name'),
            'storages' => Storage::query()->with(['site', 'terminal'])->orderBy('storage_name'),
            'fuel-trucks' => FuelTruck::query()->orderBy('truck_code'),
            'fuel-sources' => FuelSource::query()->orderBy('name'),
            'units' => Unit::query()->with(['customer', 'site'])->orderBy('unit_code'),
            'users' => User::query()->orderBy('name'),
            default => abort(404),
        };
    }

    /** @return list<string> */
    private function searchColumns(string $resource): array
    {
        return match ($resource) {
            'customers' => ['customer_code', 'customer_name'],
            'sites' => ['site_code', 'site_name'],
            'terminals' => ['terminal_code', 'terminal_name'],
            'storages' => ['storage_code', 'storage_name'],
            'fuel-trucks' => ['truck_code', 'plate_number', 'driver_name'],
            'fuel-sources' => ['code', 'name', 'source_type'],
            'units' => ['unit_code', 'unit_name', 'unit_type'],
            'users' => ['name', 'username', 'email'],
            default => abort(404),
        };
    }

    /** @return array{title: string, subtitle: string, facts: array<string, string>, status: string} */
    private function cardData(string $resource, Model $record): array
    {
        return match ($resource) {
            'customers' => ['title' => $record->customer_name, 'subtitle' => $record->customer_code, 'facts' => ['Contact' => $record->contact_person ?: '-', 'Email' => $record->email ?: '-'], 'status' => $record->status],
            'sites' => ['title' => $record->site_name, 'subtitle' => $record->site_code, 'facts' => ['Customer' => $record->customer?->customer_name ?: '-', 'Address' => $record->address ?: '-'], 'status' => $record->status],
            'terminals' => ['title' => $record->terminal_name, 'subtitle' => $record->terminal_code, 'facts' => ['Site' => $record->site?->site_name ?: '-', 'Jumlah VHS' => (string) $record->storages->count()], 'status' => $record->status],
            'storages' => ['title' => $record->storage_name, 'subtitle' => $record->storage_code, 'facts' => ['Terminal' => $record->terminal?->terminal_name ?: '-', 'Kapasitas' => number_format((float) $record->capacity_liter, 0, ',', '.').' L', 'Minimum' => number_format((float) $record->minimum_stock_liter, 0, ',', '.').' L'], 'status' => $record->status],
            'fuel-trucks' => ['title' => $record->truck_code, 'subtitle' => $record->plate_number, 'facts' => ['Kapasitas' => number_format((float) $record->capacity_liter, 0, ',', '.').' L', 'Driver' => $record->driver_name ?: '-'], 'status' => $record->status],
            'fuel-sources' => ['title' => $record->name, 'subtitle' => $record->code, 'facts' => ['Tipe' => $record->source_type, 'Alamat' => $record->address ?: '-'], 'status' => $record->status],
            'units' => ['title' => $record->unit_name, 'subtitle' => $record->unit_code, 'facts' => ['Kategori' => $record->unit_type, 'Mobilitas' => $record->mobility_type === Unit::MOBILITY_MOVABLE ? 'Bergerak' : 'Tidak Bergerak', 'Meter' => $record->meter_type], 'status' => $record->status],
            'users' => ['title' => $record->name, 'subtitle' => $record->username ?: $record->email, 'facts' => ['Email' => $record->email, 'Role' => User::roleLabels()[$record->role] ?? $record->role], 'status' => $record->is_active ? 'ACTIVE' : 'INACTIVE'],
            default => abort(404),
        };
    }

    /** @return array<string, mixed> */
    private function formLookups(): array
    {
        return [
            'customers' => Customer::query()->active()->orderBy('customer_name')->get(),
            'sites' => Site::query()->active()->orderBy('site_name')->get(),
            'terminals' => Terminal::query()->with('site')->active()->orderBy('terminal_name')->get(),
            'roles' => User::roleLabels(),
        ];
    }

    /** @return array{0: list<string>, 1: list<list<string|int|float|null>>} */
    private function pdfRows(string $resource): array
    {
        $records = $this->resourceQuery($resource)->get();

        return match ($resource) {
            'customers' => [['Kode', 'Customer', 'Contact', 'Email', 'Status'], $records->map(fn ($row): array => [$row->customer_code, $row->customer_name, $row->contact_person, $row->email, $row->status])->all()],
            'sites' => [['Kode', 'Site', 'Customer', 'Alamat', 'Status'], $records->map(fn ($row): array => [$row->site_code, $row->site_name, $row->customer?->customer_name, $row->address, $row->status])->all()],
            'terminals' => [['Kode', 'Terminal', 'Site', 'Jumlah VHS', 'Status'], $records->map(fn ($row): array => [$row->terminal_code, $row->terminal_name, $row->site?->site_name, $row->storages->count(), $row->status])->all()],
            'storages' => [['Kode', 'VHS', 'Terminal', 'Kapasitas L', 'Minimum L', 'Status'], $records->map(fn ($row): array => [$row->storage_code, $row->storage_name, $row->terminal?->terminal_name, (float) $row->capacity_liter, (float) $row->minimum_stock_liter, $row->status])->all()],
            'fuel-trucks' => [['Kode', 'Nomor Polisi', 'Kapasitas L', 'Driver', 'Status'], $records->map(fn ($row): array => [$row->truck_code, $row->plate_number, (float) $row->capacity_liter, $row->driver_name, $row->status])->all()],
            'fuel-sources' => [['Kode', 'Sumber BBM', 'Tipe', 'Alamat', 'Status'], $records->map(fn ($row): array => [$row->code, $row->name, $row->source_type, $row->address, $row->status])->all()],
            'units' => [['Kode', 'Unit', 'Kategori', 'Mobilitas', 'Meter', 'Status'], $records->map(fn ($row): array => [$row->unit_code, $row->unit_name, $row->unit_type, $row->mobility_type, $row->meter_type, $row->status])->all()],
            'users' => [['Nama', 'Username', 'Email', 'Role', 'Status'], $records->map(fn ($row): array => [$row->name, $row->username, $row->email, User::roleLabels()[$row->role] ?? $row->role, $row->is_active ? 'ACTIVE' : 'INACTIVE'])->all()],
            default => abort(404),
        };
    }

    /** @return class-string<Model> */
    private function modelClass(string $resource): string
    {
        return match ($resource) {
            'customers' => Customer::class,
            'sites' => Site::class,
            'terminals' => Terminal::class,
            'storages' => Storage::class,
            'fuel-trucks' => FuelTruck::class,
            'fuel-sources' => FuelSource::class,
            'units' => Unit::class,
            'users' => User::class,
            default => abort(404),
        };
    }

    private function moduleName(string $resource): string
    {
        return $this->resources()[$resource]['singular'] ?? abort(404);
    }

    /** @return array<string, mixed> */
    private function normalizedData(MasterDataRequest $request): array
    {
        $data = $request->validated();

        if ($request->route('resource') === 'users') {
            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $data['is_active'] = (bool) $data['is_active'];
        }

        return $data;
    }

    private function reference(Model $model): string
    {
        return (string) match (true) {
            isset($model->customer_code) => $model->customer_code,
            isset($model->site_code) => $model->site_code,
            isset($model->terminal_code) => $model->terminal_code,
            isset($model->storage_code) => $model->storage_code,
            isset($model->truck_code) => $model->truck_code,
            isset($model->unit_code) => $model->unit_code,
            isset($model->code) => $model->code,
            isset($model->email) => $model->email,
            default => $model->getKey(),
        };
    }
}
