@extends('layouts.app', ['pageTitle' => 'Master Data'])

@section('actions')
    <div class="d-flex gap-2">
        <a href="{{ route('master.export-pdf', $resource) }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
        </a>
        <a href="{{ route('master.create', $resource) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah {{ $module['singular'] }}
        </a>
    </div>
@endsection

@section('content')
    <nav class="master-resource-nav mb-3" aria-label="Master data category">
        @foreach($resources as $key => $item)
            <a href="{{ route('master.index', ['resource' => $key]) }}" class="master-resource-link @if($resource === $key) active @endif">
                <i class="bi {{ $item['icon'] }}"></i>
                <span>{{ $item['plural'] }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="card mb-3">
        <input type="hidden" name="resource" value="{{ $resource }}">
        <div class="card-body d-flex flex-column flex-md-row gap-2">
            <div class="flex-grow-1">
                <label class="visually-hidden" for="master-search">Cari {{ $module['plural'] }}</label>
                <input id="master-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Cari kode atau nama">
            </div>
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button>
        </div>
    </form>

    <div class="master-card-grid">
        @forelse($cards as $card)
            <article class="card master-data-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                        <div class="master-card-heading">
                            <div class="master-card-icon"><i class="bi {{ $module['icon'] }}"></i></div>
                            <div class="min-w-0">
                                <div class="metric-label">{{ $card['subtitle'] }}</div>
                                <h2 class="master-card-title">{{ $card['title'] }}</h2>
                            </div>
                        </div>
                        @include('partials.status', ['status' => $card['status']])
                    </div>

                    <dl class="master-card-facts">
                        @foreach($card['facts'] as $label => $value)
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('master.edit', [$resource, $card['record']->id]) }}" class="btn btn-outline-secondary btn-sm" title="Edit {{ $module['singular'] }}">
                        <i class="bi bi-pencil-square me-1"></i>Edit
                    </a>
                </div>
            </article>
        @empty
            <div class="card master-empty-state">
                <div class="card-body text-center py-5">
                    <i class="bi {{ $module['icon'] }}"></i>
                    <h2 class="section-title mt-3">Belum ada {{ $module['plural'] }}</h2>
                    <a href="{{ route('master.create', $resource) }}" class="btn btn-primary btn-sm mt-2"><i class="bi bi-plus-lg me-1"></i>Tambah Data</a>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $cards->links() }}</div>
@endsection
