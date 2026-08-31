@props([
    'data',                       // array<int, array{label: string, nilai: float, ket?: string}>
    'satuan' => 'halaman',
    'warna' => null,
])

@php
    use App\Support\Grafik;
    use Illuminate\Support\Str;

    $warna = $warna ?? Grafik::BATANG;
    $lebar = 720;

    // Tebal batang dibatasi; sisa jaraknya sengaja dibiarkan menjadi udara.
    $tebal = 18;
    $jarak = 14;                   // jauh di atas 2px pemisah minimum antar batang
    $kiri = 176;                   // ruang nama kategori
    $kanan = 64;                   // ruang angka di ujung batang
    $atas = 6;

    $plotLebar = $lebar - $kiri - $kanan;
    $tinggi = max($atas * 2 + count($data) * ($tebal + $jarak) - $jarak, 40);
    $batas = Grafik::batasAtas((float) collect($data)->max('nilai'));

    $baris = [];
    foreach (array_values($data) as $i => $item) {
        $nilai = (float) $item['nilai'];
        $y = $atas + $i * ($tebal + $jarak);
        $x1 = $kiri + ($batas > 0 ? ($nilai / $batas) * $plotLebar : 0);

        $baris[] = [
            'i' => $i,
            'label' => $item['label'],
            'pendek' => Str::limit($item['label'], 24),
            'ket' => $item['ket'] ?? null,
            'nilai' => Grafik::angka($nilai),
            'jalur' => Grafik::jalurBatang($kiri, $y, $x1, $tebal),
            'yTeks' => $y + $tebal / 2 + 4,
            'xTeks' => round($x1 + 8, 2),
            'yHit' => $y - $jarak / 2,
        ];
    }
@endphp

<div x-data="{ aktif: null }" class="relative">
    <svg viewBox="0 0 {{ $lebar }} {{ $tinggi }}" class="h-auto w-full"
         role="img" aria-label="Grafik batang {{ $satuan }} per kategori">

        {{-- Garis dasar tunggal: seluruh batang tumbuh dari sini. --}}
        <line x1="{{ $kiri }}" y1="{{ $atas }}" x2="{{ $kiri }}" y2="{{ $tinggi - $atas }}"
              stroke="{{ Grafik::SUMBU }}" stroke-width="1" />

        @foreach ($baris as $b)
            {{-- Nama kategori memakai tinta teks, bukan warna data. --}}
            <text x="{{ $kiri - 10 }}" y="{{ $b['yTeks'] }}" text-anchor="end"
                  font-size="11" fill="#334155">{{ $b['pendek'] }}</text>

            <path d="{{ $b['jalur'] }}" fill="{{ $warna }}"
                  :opacity="aktif === null || aktif === {{ $b['i'] }} ? 1 : 0.45"
                  style="transition: opacity 120ms" />

            <text x="{{ $b['xTeks'] }}" y="{{ $b['yTeks'] }}"
                  font-size="11" font-weight="600" fill="#334155"
                  style="font-variant-numeric: tabular-nums">{{ $b['nilai'] }}</text>

            {{-- Sasaran sorot lebih besar daripada batangnya. --}}
            <rect x="0" y="{{ $b['yHit'] }}" width="{{ $lebar }}" height="{{ $tebal + $jarak }}"
                  fill="transparent"
                  @pointerenter="aktif = {{ $b['i'] }}" @pointerleave="aktif = null"
                  tabindex="0" @focus="aktif = {{ $b['i'] }}" @blur="aktif = null">
                <title>{{ $b['label'] }}: {{ $b['nilai'] }} {{ $satuan }}@if ($b['ket']) — {{ $b['ket'] }}@endif</title>
            </rect>
        @endforeach
    </svg>

    <details class="mt-3 text-xs">
        <summary class="cursor-pointer text-slate-500 hover:text-slate-700">Lihat sebagai tabel</summary>
        <div class="mt-2 overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-slate-500">
                    <tr>
                        <th class="py-1 pr-4 font-medium">Nama</th>
                        <th class="py-1 pr-4 font-medium">{{ Str::ucfirst($satuan) }}</th>
                        <th class="py-1 font-medium">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach ($baris as $b)
                        <tr>
                            <td class="py-1 pr-4">{{ $b['label'] }}</td>
                            <td class="py-1 pr-4" style="font-variant-numeric: tabular-nums">{{ $b['nilai'] }}</td>
                            <td class="py-1 text-slate-500">{{ $b['ket'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</div>
