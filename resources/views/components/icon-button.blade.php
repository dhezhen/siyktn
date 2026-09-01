@props([
    'icon',
    'label',                 // wajib: sekarang menjadi teks di dalam tombol
    'variant' => 'default',
    'href' => null,
])

@php
    $buttonVariant = match($variant) {
        'primary' => 'primary',
        'danger' => 'danger',
        'warning' => 'warning',
        'default' => 'secondary',
        default => 'secondary',
    };

    $labelLower = strtolower($label);
    $shortLabel = match (true) {
        str_contains($labelLower, 'lihat detail') => 'Detail',
        str_contains($labelLower, 'ubah') => 'Ubah',
        str_contains($labelLower, 'hapus') => 'Hapus',
        str_contains($labelLower, 'tambah') => 'Tambah',
        str_contains($labelLower, 'atur') => 'Atur',
        str_contains($labelLower, 'pulihkan') => 'Pulihkan',
        str_contains($labelLower, 'tutup') => 'Tutup',
        str_contains($labelLower, 'tanpa berkas') => 'Tanpa KTP',
        default => $label,
    };
@endphp

<x-button :href="$href" :icon="$icon" size="sm" :variant="$buttonVariant" title="{{ $label }}" {{ $attributes }}>
    {{ $shortLabel }}
</x-button>
