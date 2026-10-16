@props([
    'action',
    'method' => 'POST',
    'title',
    'leftTitle',
    'rightTitle',
    'buttonLabel' => 'Save',
    'buttonClass' => 'btn-primary',
    'compact' => false,
])

<form method="POST" action="{{ $action }}" class="card master-entry-form {{ $compact ? 'master-entry-form--compact' : '' }}">
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="card-header master-entry-header">
        <h2 class="section-title mb-0">{{ $title }}</h2>
    </div>
    <div class="card-body p-0">
        <div class="entry-grid">
            <section class="entry-pane">
                <div class="entry-pane-header">
                    <span class="entry-pane-icon"><i class="bi bi-card-checklist"></i></span>
                    <h3 class="entry-pane-title">{{ $leftTitle }}</h3>
                </div>
                {{ $left }}
            </section>
            <section class="entry-pane">
                <div class="entry-pane-header">
                    <span class="entry-pane-icon"><i class="bi bi-sliders"></i></span>
                    <h3 class="entry-pane-title">{{ $rightTitle }}</h3>
                </div>
                {{ $right }}
            </section>
        </div>
    </div>
    <div class="card-footer text-end">
        <button type="submit" class="btn {{ $buttonClass }} {{ $compact ? 'btn-sm' : '' }}">
            <i class="bi bi-save me-1"></i>{{ $buttonLabel }}
        </button>
    </div>
</form>
