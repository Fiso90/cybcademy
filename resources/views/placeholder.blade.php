@extends('layouts.app')

@section('title', ($title ?? 'CybCademy') . ' - CybCademy')

{{--
    Honest placeholder for screens whose API/backend already exists
    (Epics E2-E7) but whose dedicated Blade template wasn't part of this
    Phase 11 slice - see routes/web.php's comment for the full list and
    why. Better than a 404 or an error page, and better than silently
    hiding the nav item, which would misrepresent what's actually built.
--}}

@section('content')
<div class="cyb-card text-center py-5">
    <i class="bi bi-tools fs-1" style="color: var(--cyb-text-muted);"></i>
    <h1 class="cyb-display h4 mt-3">{{ $title }}</h1>
    <p class="text-muted mb-0">This screen's backend is complete - the dedicated interface for it is still being built.</p>
</div>
@endsection
