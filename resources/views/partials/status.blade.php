@php
    $statusClass = [
        'DRAFT' => 'text-bg-secondary',
        'OPEN' => 'text-bg-primary',
        'PARTIAL' => 'text-bg-warning',
        'COMPLETED' => 'text-bg-success',
        'POSTED' => 'text-bg-success',
        'FINAL' => 'text-bg-success',
        'SUBMITTED' => 'text-bg-info',
        'APPROVED' => 'text-bg-success',
        'REVERSED' => 'text-bg-danger',
        'CANCELLED' => 'text-bg-dark',
        'ACTIVE' => 'text-bg-success',
        'INACTIVE' => 'text-bg-secondary',
    ][$status] ?? 'text-bg-light';
@endphp

<span class="badge status-badge {{ $statusClass }}">{{ $status }}</span>
