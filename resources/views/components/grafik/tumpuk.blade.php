@props([
    'data',                  // array<int, array{label: string, nilai: int, warna: string}>
    'satuan' => 'setoran',
])

@php
    use App\Support\Grafik;

    $lebar = 720;
    $tebal = 22;
    $total = max(array_sum(array_column($data, 'nilai')), 1);
    $celah = 2;              // pemisahnya udara, bukan garis tepi

    $segmen = [];
    $x = 0;

    foreach ($data as $item) {
        $porsi = $item['nilai'] / $total;
        $lebarSegmen = max($porsi * ($lebar - $celah * (count($data) - 1)), 0);

        $segmen[] = [
            'x' => $x,
            'lebar' => $lebarSegmen,
            'persen' => $porsi * 100,
        ] + $item;

        $x += $lebarSegmen + $celah;
    }
@endphp

<div x-data="{ aktif: null }">
    <svg viewBox="0 0 {{ $lebar }} {{ $tebal }}" class="h-auto w-full"
         role="img" aria-label="Sebaran kualitas {{ $satuan }}">
        @foreach ($segmen as $i => $s)
            @if ($s['nilai'] > 0)
                <rect x="{{ round($s['x'], 2) }}" y="0"
                      width="{{ round($s['lebar'], 2) }}" height="{{ $tebal }}"
                      rx="{{ $s['lebar'] > 8 ? 3 : 0 }}"
                      fill="{{ $s['warna'] }}"
                      :opacity="aktif === null || aktif === {{ $i }} ? 1 : 0.45"
                      style="transition: opacity 120ms"
                      @pointerenter="aktif = {{ $i }}" @pointerleave="aktif = null"
                      tabindex="0" @focus="aktif = {{ $i }}" @blur="aktif = null">
                    <title>{{ $s['label'] }}: {{ $s['nilai'] }} {{ $satuan }} ({{ Grafik::angka($s['persen'], 0) }}%)</title>
                </rect>
            @endif
        @endforeach
    </svg>

    {{--
        Legenda ini wajib, bukan pelengkap: dua dari empat warna berada di bawah
        kontras 3:1 terhadap kartu putih, jadi angkanya harus tertulis dan tidak
        boleh hanya diwakili warna.
    --}}
    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
        @foreach ($segmen as $i => $s)
            <li class="flex items-center gap-2 text-xs"
                @pointerenter="aktif = {{ $i }}" @pointerleave="aktif = null">
                <span class="size-2.5 shrink-0 rounded-sm" style="background-color: {{ $s['warna'] }}"></span>
                <span class="flex-1 text-slate-600 dark:text-slate-400">{{ $s['label'] }}</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200" style="font-variant-numeric: tabular-nums">
                    {{ $s['nilai'] }}
                </span>
                <span class="w-10 text-right text-slate-400 dark:text-slate-500" style="font-variant-numeric: tabular-nums">
                    {{ Grafik::angka($s['persen'], 0) }}%
                </span>
            </li>
        @endforeach
    </ul>
</div>
