@php
    $variant = $variant ?? 'light';
    $logo = $variant === 'dark' ? 'sweetcrumbs-logo-dark.png' : 'sweetcrumbs-logo.png';
@endphp

<img
    src="{{ asset($logo) }}?v={{ filemtime(public_path($logo)) }}"
    alt="SweetCrumbs"
    class="{{ $class ?? 'h-14 w-auto' }} block max-w-full object-contain"
>
