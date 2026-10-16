@extends('layouts.app', ['title' => 'Login | VHS Fuel Monitoring Portal'])

@section('content')
    <section class="auth-panel" aria-labelledby="login-title">
        <div class="auth-brand-panel">
            <div>
                <img src="{{ asset('images/logo-indra-angkola.png') }}" alt="Logo PT Indra Angkola Energy" class="auth-brand-logo">
                <p class="auth-eyebrow">PT Indra Angkola Energy</p>
                <h1>Fuel Monitoring Portal</h1>
                <p class="auth-brand-copy">
                    Monitoring penerimaan, distribusi, dan rekonsiliasi bahan bakar dalam satu kontrol operasional.
                </p>
            </div>

            <div class="auth-scope">
                <div class="auth-scope-item">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <span>Controlled access</span>
                </div>
                <div class="auth-scope-item">
                    <i class="bi bi-building-check" aria-hidden="true"></i>
                    <span>PT Indra Angkola Energy</span>
                </div>
            </div>
        </div>

        <div class="auth-form-panel">
            <div class="auth-form-content">
                <div class="auth-form-heading">
                    <p class="auth-eyebrow">Secure sign in</p>
                    <h2 id="login-title">Masuk ke portal</h2>
                    <p>Gunakan akun operasional yang telah diberikan.</p>
                </div>

                @include('partials.alerts')

                <form method="POST" action="{{ url('/login') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="login" class="form-label">Username atau email</label>
                        <div class="input-group auth-input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-person"></i></span>
                            <input id="login" type="text" name="login" value="{{ old('login') }}"
                                class="form-control @error('login') is-invalid @enderror"
                                autocomplete="username" placeholder="Masukkan username atau email" required autofocus>
                        </div>
                        @error('login')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group auth-input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" name="password" class="form-control"
                                autocomplete="current-password" placeholder="Masukkan password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        <span>Masuk</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <p class="auth-help mb-0">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    Hubungi administrator VHS apabila akses akun bermasalah.
                </p>
                <p class="auth-copyright mb-0">
                    Copyright &copy; {{ now()->year }} PT Indra Angkola Energy
                </p>
            </div>
        </div>
    </section>
@endsection
