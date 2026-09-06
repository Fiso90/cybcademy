<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify it's you - CybCademy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="cyb-card" style="width: 380px;">
        <h1 class="cyb-display h4 mb-1">Verify it's you</h1>
        <p class="text-muted mb-4">Enter the 6-digit code from your authenticator app. Required for your role per organisation security policy.</p>

        @if ($errors->any())
            <div class="alert alert-danger py-2" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('auth.mfa.verify') }}">
            @csrf
            <div class="mb-3">
                <label for="code" class="form-label">Verification code</label>
                <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                       class="form-control cyb-mono text-center fs-4" id="code" name="code"
                       required autofocus autocomplete="one-time-code">
            </div>
            <button type="submit" class="btn w-100" style="background-color: var(--cyb-accent); color: #fff;">
                Verify
            </button>
        </form>
    </div>
</body>
</html>
