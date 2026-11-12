@php($value = round((float) ($rating ?? 0)))
<span class="inline-flex items-center gap-0.5 text-gold" aria-label="{{ $value }} out of 5">
    @for ($star = 1; $star <= 5; $star++)
        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 {{ $star <= $value ? 'fill-current' : 'fill-ink/10' }}" aria-hidden="true">
            <path d="M10 1.6l2.47 5.01 5.53.8-4 3.9.94 5.51L10 14.22 5.06 16.8 6 11.29l-4-3.9 5.53-.8L10 1.6z" />
        </svg>
    @endfor
</span>
