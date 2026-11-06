@props(['class' => ''])

@php
    $notifications = [
        ['message' => session('sukses'), 'style' => 'border-sekunder bg-sekunder/10 text-sekunder', 'label' => 'Berhasil'],
        ['message' => session('info'), 'style' => 'border-primer bg-primer/10 text-primer', 'label' => 'Informasi'],
        ['message' => session('galat') ?? session('error'), 'style' => 'border-bahaya bg-bahaya/10 text-bahaya', 'label' => 'Perlu diperhatikan'],
    ];
@endphp

<div {{ $attributes->class($class) }} aria-live="polite">
    @foreach ($notifications as $notification)
        @if ($notification['message'])
            <div class="mb-3 rounded-sm border-l-4 px-4 py-3 text-base {{ $notification['style'] }}">
                <strong>{{ $notification['label'] }}:</strong> {{ $notification['message'] }}
            </div>
        @endif
    @endforeach
    @if ($errors->any())
        <div class="rounded-sm border-l-4 border-bahaya bg-bahaya/10 px-4 py-3 text-base text-bahaya">
            <p class="font-semibold">Periksa kembali isian berikut.</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>