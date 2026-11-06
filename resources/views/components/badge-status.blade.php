@props(['status', 'label' => null])

@php
    $styles = [
        'diproses' => 'bg-peringatan-latar text-peringatan',
        'diterima' => 'bg-sekunder/10 text-sekunder',
        'ditolak' => 'bg-bahaya/10 text-bahaya',
        'menunggu' => 'bg-peringatan-latar text-peringatan',
        'berhasil' => 'bg-sekunder/10 text-sekunder',
        'gagal' => 'bg-bahaya/10 text-bahaya',
        'kedaluwarsa' => 'bg-garis text-teks',
        'belum_dibayar' => 'bg-peringatan-latar text-peringatan',
        'lunas' => 'bg-sekunder/10 text-sekunder',
        'dibuka' => 'bg-sekunder/10 text-sekunder',
        'ditutup' => 'bg-garis text-teks',
    ];
    $text = $label ?? str($status)->replace('_', ' ')->title();
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold', $styles[$status] ?? 'bg-garis text-teks']) }}>{{ $text }}</span>