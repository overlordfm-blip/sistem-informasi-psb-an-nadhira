@props(['varian' => 'utama', 'type' => 'button'])

@php
    $classes = [
        'utama' => 'bg-primer text-white hover:bg-sekunder',
        'sekunder' => 'bg-aksen text-teks hover:brightness-95',
        'bahaya' => 'bg-bahaya text-white hover:brightness-90',
    ];
    $class = 'inline-flex min-h-11 items-center justify-center rounded-sm px-5 py-2 text-base font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primer disabled:cursor-not-allowed disabled:opacity-60 '.$classes[$varian];
@endphp

@if ($attributes->has('href'))
    <a {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif