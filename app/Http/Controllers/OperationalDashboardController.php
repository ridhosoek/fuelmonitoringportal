<?php

namespace App\Http\Controllers;

use App\Http\Requests\OperationalDashboardRequest;
use App\Models\Site;
use App\Models\Storage;
use App\Services\OperationalDashboardService;
use Illuminate\View\View;

class OperationalDashboardController extends Controller
{
    public function __invoke(
        OperationalDashboardRequest $request,
        OperationalDashboardService $dashboardService,
    ): View {
        return view('operational-dashboard', [
            'dashboard' => $dashboardService->overview($request->validated()),
            'sites' => Site::query()->active()->orderBy('site_name')->get(),
            'storages' => Storage::query()
                ->active()
                ->with(['site', 'terminal'])
                ->orderBy('storage_name')
                ->get(),
        ]);
    }
}
