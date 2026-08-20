@props(['value' => '', 'currency' => 'SAR'])

@php
    $parts = strtoupper((string) $currency) === 'SAR'
        ? preg_split('/(\bSAR\b)/u', (string) $value, -1, PREG_SPLIT_DELIM_CAPTURE)
        : [(string) $value];
@endphp

<span {{ $attributes }}>
    @foreach($parts as $part)
        @if($part === 'SAR')
            <x-currency-symbol currency="SAR" />
        @else
            {{ $part }}
        @endif
    @endforeach
</span>
