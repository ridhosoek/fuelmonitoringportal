@extends('layouts.app', ['pageTitle' => 'Audit Trail'])

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body filter-grid">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="Date from">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="Date to">
            <select name="module" class="form-select" aria-label="Module">
                <option value="">All Module</option>
                @foreach($modules as $module)
                    <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                @endforeach
            </select>
            <select name="action" class="form-select" aria-label="Action">
                <option value="">All Action</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <select name="user_id" class="form-select" aria-label="User">
                <option value="">All User</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Reference">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Reference</th><th>IP</th><th>New Values</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $auditLog)
                        <tr>
                            <td>{{ $auditLog->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $auditLog->user?->name ?: '-' }}</td>
                            <td><span class="badge text-bg-primary">{{ $auditLog->action }}</span></td>
                            <td>{{ $auditLog->module }}</td>
                            <td>{{ $auditLog->reference ?: '-' }}</td>
                            <td>{{ $auditLog->ip_address ?: '-' }}</td>
                            <td><code>{{ str($auditLog->new_values ? json_encode($auditLog->new_values) : '-')->limit(100) }}</code></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No audit log data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $auditLogs->links() }}</div>
    </div>
@endsection
