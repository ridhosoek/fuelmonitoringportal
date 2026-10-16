<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Storage;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'storage_id' => ['nullable', 'exists:storages,id'],
        ]);

        return view('dashboard', [
            'overview' => $dashboardService->overview($filters),
            'sites' => Site::query()->orderBy('site_name')->get(),
            'storages' => Storage::query()->with(['site', 'terminal'])->orderBy('storage_name')->get(),
        ]);
    }
}
