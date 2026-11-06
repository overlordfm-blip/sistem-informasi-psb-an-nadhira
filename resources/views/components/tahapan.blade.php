@props(['steps', 'current' => 1])

<ol {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4']) }} aria-label="Tahapan">
    @foreach ($steps as $index => $step)
        @php($number = $index + 1)
        <li class="flex min-h-32 flex-col items-center justify-center gap-3 rounded-sm border border-garis bg-permukaan p-4 text-center">
            <span class="flex size-10 items-center justify-center rounded-full {{ $number <= $current ? 'bg-primer text-white' : 'bg-latar text-teks' }} font-bold" aria-hidden="true">{{ $number }}</span>
            <span class="text-base font-semibold">{{ $step }}</span>
            @if ($number === $current)
                <span class="sr-only">Tahap saat ini</span>
            @endif
        </li>
    @endforeach
</ol>