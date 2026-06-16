<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Verdantia</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="auth-logo mb-3">@include('partials.icon', ['name' => 'leaf', 'size' => 26])</div>
            <div style="font-weight:700; font-size:1.4rem;">Verdantia</div>
            <div class="brand-sub" style="color: var(--accent); letter-spacing:.12em; font-size:.7rem;">GREENHOUSE OS</div>
        </div>

        <div class="gh-card">
            @if (session('status'))
                <div class="alert alert-success py-2">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           required autofocus autocomplete="username">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                        <label class="form-check-label" for="remember_me">Remember me</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-decoration-none" style="color: var(--accent); font-size:.88rem;">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="btn btn-accent w-100 py-2">Sign in</button>
            </form>
        </div>

        <div class="text-center mt-3 text-muted-2" style="font-size:.82rem;">
            @include('partials.icon', ['name' => 'lock', 'size' => 13]) Local network access only
        </div>
    </div>
</div>
</body>
</html>
