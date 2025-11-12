@props(['locale' => 'id', 'class' => ''])

@php
    $code = strtoupper(substr((string) $locale, 0, 2));
@endphp

<span {{ $attributes->merge(['class' => trim('language-flag ' . $class)]) }} aria-hidden="true">
    @if ($code === 'EN')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-full w-full">
            <rect width="24" height="24" fill="#0A47A9" />
            <path fill="#ffffff" d="M0 10.5h24v3H0zM10.5 0h3v24h-3z" />
            <path fill="#ffffff" d="M0 0l9 6H6L0 2.2zM24 0l-9 6h3l6-3.8zM0 24l9-6H6l-6 3.8zM24 24l-9-6h3l6 3.8z" />
            <path fill="#E11D48" d="M0 11h24v2H0zM11 0h2v24h-2z" />
            <path fill="#E11D48" d="M0 0l8 5.3H5.5L0 1.9zM24 0l-8 5.3h2.5L24 1.9zM0 24l8-5.3H5.5L0 22.1zM24 24l-8-5.3h2.5L24 22.1z" />
        </svg>
    @else
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-full w-full">
            <rect width="24" height="12" fill="#E11D48" />
            <rect y="12" width="24" height="12" fill="#ffffff" />
        </svg>
    @endif
</span>
