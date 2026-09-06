{{--
    Risk Ring - the signature visual element referenced throughout Phase 6
    ("used everywhere a Human Risk Score appears - employee card,
    department heatmap, executive dashboard hero, certificate issuance").
    One component, used everywhere, so the platform's core metric has a
    single recognisable identity rather than five different "number in a
    box" treatments across different screens.

    Props:
      score (float, 0-100, higher = lower risk - see Phase 10 Epic E6
             HumanRiskScoreCalculator's docblock for why the direction is
             inverted from what might seem intuitive)
      size (int, px - default 120)
      label (string, optional - text below the number, e.g. "Organisation"
             or an employee/department name)

    Colour tier and the text label are ALWAYS rendered together (Phase 6
    Section 8: "colour is never the sole indicator of risk state") - the
    tier name is present in the DOM even when driven primarily by colour,
    satisfying WCAG's "not by colour alone" requirement without relying on
    the consuming page to remember to add it separately.

    Animation (the ring "filling up") respects prefers-reduced-motion via
    the CSS media query in app.css, which strips the transition-duration
    globally - no separate JS branch needed here.
--}}
@props(['score' => 0, 'size' => 120, 'label' => null])

@php
    $tier = match(true) {
        $score >= 80 => 'low',
        $score >= 50 => 'medium',
        default => 'high',
    };
    $tierLabel = match($tier) {
        'low' => 'Low Risk',
        'medium' => 'Medium Risk',
        'high' => 'High Risk',
    };
    $strokeColorVar = match($tier) {
        'low' => '--cyb-risk-low',
        'medium' => '--cyb-risk-medium',
        'high' => '--cyb-risk-high',
    };
    $radius = ($size / 2) - 10;
    $circumference = 2 * pi() * $radius;
    $offset = $circumference - ($score / 100) * $circumference;
@endphp

<div class="cyb-risk-ring" style="width: {{ $size }}px;" role="img"
     aria-label="{{ ($label ? $label . ': ' : '') . round($score) . ' out of 100, ' . $tierLabel }}">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" aria-hidden="true">
        <circle
            cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}"
            fill="none" stroke="var(--cyb-border)" stroke-width="8"
        />
        <circle
            cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}"
            fill="none" stroke="var({{ $strokeColorVar }})" stroke-width="8"
            stroke-linecap="round"
            stroke-dasharray="{{ $circumference }}"
            stroke-dashoffset="{{ $offset }}"
            transform="rotate(-90 {{ $size / 2 }} {{ $size / 2 }})"
            style="transition: stroke-dashoffset 800ms ease;"
        />
        <text x="50%" y="50%" text-anchor="middle" dominant-baseline="central"
              font-family="var(--cyb-font-display)" font-size="{{ $size * 0.22 }}"
              font-weight="700" fill="var(--cyb-text)">
            {{ round($score) }}
        </text>
    </svg>
    <div class="text-center mt-1">
        <span class="cyb-badge cyb-badge--{{ $tier }}">{{ $tierLabel }}</span>
        @if($label)
            <div class="small mt-1" style="color: var(--cyb-text-muted);">{{ $label }}</div>
        @endif
    </div>
</div>
