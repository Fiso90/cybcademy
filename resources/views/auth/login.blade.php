<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in - CybCademy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="cyb-card" style="width: 380px;">
        <h1 class="cyb-display h4 mb-1">CybCademy</h1>
        <p class="text-muted mb-4">Sign in to continue</p>

        @if ($errors->any())
            <div class="alert alert-danger py-2" role="alert">
                {{-- Errors are specific and instructive per Phase 6
                     Section 8: "Password must include a number," not
                     "Invalid input." AuthService/LoginRequest already
                     produce specific messages; this just renders them. --}}
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.login.attempt') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn w-100" style="background-color: var(--cyb-accent); color: #fff;">
                Sign in
            </button>
        </form>
    </div>
</body>
</html>
