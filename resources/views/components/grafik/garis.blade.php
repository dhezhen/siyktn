@props([
    'labels',        // array<int, string>  — label sumbu X
    'seri',          // array<int, array{nama: string, warna: string, data: array<int, float>}>
    'satuan' => 'halaman',
    'tinggi' => 260,
])

@php
    use App\Support\Grafik;
    use Illuminate\Support\Js;

    /*
     | Seluruh geometri dihitung di sini, sehingga bagian markup di bawah tidak
     | perlu menyisipkan PHP sama sekali. Bentuk sebaris @php(...) sengaja
     | dihindari: dengan pemanggilan fungsi bersarang ia dikompilasi menjadi
     | blok PHP tanpa penutup dan menelan sisa berkasnya.
     */
    $lebar = 720;
    $tinggi = (int) $tinggi;

    // Ruang kiri untuk angka sumbu, kanan untuk label ujung garis.
    $kiri = 46; $kanan = 62; $atas = 18; $bawah = 30;

    $plotLebar = $lebar - $kiri - $kanan;
    $plotTinggi = $tinggi - $atas - $bawah;
    $plotKanan = $kiri + $plotLebar;
    $plotBawah = $atas + $plotTinggi;

    $jumlahTitik = max(count($labels), 1);
    $batas = Grafik::batasAtas((float) collect($seri)->flatMap(fn ($s) => $s['data'])->max());

    $posX = fn (int $i) => $jumlahTitik === 1
        ? $kiri + $plotLebar / 2
        : $kiri + $plotLebar * $i / ($jumlahTitik - 1);

    $posY = fn (float $v) => $atas + $plotTinggi - ($batas > 0 ? ($v / $batas) * $plotTinggi : 0);

    // Garis bantu beserta angkanya.
    $garisBantu = [];
    foreach (Grafik::tanda($batas) as $nilai) {
        $garisBantu[] = [
            'y' => round($posY($nilai), 2),
            'teks' => Grafik::angka($nilai, 0),
            'warna' => $nilai == 0 ? Grafik::SUMBU : Grafik::GARIS_BANTU,
        ];
    }

    // Label sumbu X, dijarangkan agar tidak saling menimpa.
    $labelX = [];
    foreach ($labels as $i => $teks) {
        if ($jumlahTitik <= 8 || $i % 2 === 0 || $i === $jumlahTitik - 1) {
            $labelX[] = ['x' => round($posX($i), 2), 'teks' => $teks];
        }
    }

    $siap = [];
    foreach ($seri as $s) {
        $titik = [];
        foreach ($s['data'] as $i => $v) {
            $titik[] = ($i === 0 ? 'M' : 'L').round($posX($i), 2).','.round($posY((float) $v), 2);
        }

        $akhir = (float) end($s['data']);

        $siap[] = [
            'nama' => $s['nama'],
            'warna' => $s['warna'],
            'jalur' => implode(' ', $titik),
            'xAkhir' => round($posX($jumlahTitik - 1), 2),
            'yAkhir' => round($posY($akhir), 2),
            'nilaiAkhir' => Grafik::angka($akhir),
            'y' => array_map(fn ($v) => round($posY((float) $v), 2), $s['data']),
        ];
    }

    /*
     | Label ujung hanya dipasang bila kedua garis cukup renggang. Bila
     | berdempetan, angkanya dibiarkan dibawa legenda, tooltip, dan tabel —
     | menggeser label menjauh dari garisnya justru terbaca sebagai derau.
     */
    $bolehLabelUjung = count($siap) < 2 || abs($siap[0]['yAkhir'] - $siap[1]['yAkhir']) >= 18;

    $titikHover = [];
    foreach ($labels as $i => $teks) {
        $titikHover[] = [
            'x' => round($posX($i), 2),
            'label' => $teks,
            'nilai' => array_map(fn ($s) => Grafik::angka((float) $s['data'][$i]), $seri),
        ];
    }

    $jsSeri = array_map(fn ($s) => ['nama' => $s['nama'], 'warna' => $s['warna']], $seri);
    $jsSeriY = array_map(fn ($s) => $s['y'], $siap);
@endphp

<div x-data="{
        aktif: null,
        titik: {{ Js::from($titikHover) }},
        seri: {{ Js::from($jsSeri) }},
        seriY: {{ Js::from($jsSeriY) }},
        arahkan(e) {
            const kotak = $refs.svg.getBoundingClientRect();
            const x = (e.clientX - kotak.left) / kotak.width * {{ $lebar }};
            let dekat = 0;
            this.titik.forEach((t, i) => {
                if (Math.abs(t.x - x) < Math.abs(this.titik[dekat].x - x)) dekat = i;
            });
            this.aktif = dekat;
        },
     }"
     class="relative">

    {{-- Legenda: kanal identitas yang tidak bergantung pada warna saja. --}}
    @if (count($siap) > 1)
        <div class="mb-3 flex flex-wrap items-center gap-4">
            @foreach ($siap as $s)
                <span class="inline-flex items-center gap-2 text-xs text-slate-600">
                    <svg width="14" height="4" aria-hidden="true" class="shrink-0">
                        <line x1="0" y1="2" x2="14" y2="2" stroke="{{ $s['warna'] }}" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    {{ $s['nama'] }}
                </span>
            @endforeach
        </div>
    @endif

    <svg x-ref="svg" viewBox="0 0 {{ $lebar }} {{ $tinggi }}" class="h-auto w-full"
         role="img" aria-label="Grafik garis {{ collect($siap)->pluck('nama')->join(' dan ') }} dalam {{ $satuan }}"
         @pointermove="arahkan($event)" @pointerleave="aktif = null">

        {{-- Garis bantu: hairline, tidak putus-putus, sengaja tidak menonjol. --}}
        @foreach ($garisBantu as $g)
            <line x1="{{ $kiri }}" y1="{{ $g['y'] }}" x2="{{ $plotKanan }}" y2="{{ $g['y'] }}"
                  stroke="{{ $g['warna'] }}" stroke-width="1" />
            <text x="{{ $kiri - 8 }}" y="{{ $g['y'] + 3.5 }}" text-anchor="end"
                  font-size="10" fill="{{ Grafik::TINTA_REDUP }}" style="font-variant-numeric: tabular-nums">{{ $g['teks'] }}</text>
        @endforeach

        @foreach ($labelX as $l)
            <text x="{{ $l['x'] }}" y="{{ $tinggi - 10 }}" text-anchor="middle"
                  font-size="10" fill="{{ Grafik::TINTA_REDUP }}">{{ $l['teks'] }}</text>
        @endforeach

        <template x-if="aktif !== null">
            <line :x1="titik[aktif].x" :x2="titik[aktif].x" y1="{{ $atas }}" y2="{{ $plotBawah }}"
                  stroke="{{ Grafik::SUMBU }}" stroke-width="1" />
        </template>

        @foreach ($siap as $s)
            <path d="{{ $s['jalur'] }}" fill="none" stroke="{{ $s['warna'] }}"
                  stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

            {{-- Titik ujung: cincin permukaan 2px agar tetap terbaca saat bersilangan. --}}
            <circle cx="{{ $s['xAkhir'] }}" cy="{{ $s['yAkhir'] }}" r="4"
                    fill="{{ $s['warna'] }}" stroke="{{ Grafik::PERMUKAAN }}" stroke-width="2" />

            @if ($bolehLabelUjung)
                <text x="{{ $s['xAkhir'] + 10 }}" y="{{ $s['yAkhir'] + 3.5 }}"
                      font-size="11" font-weight="600" fill="#334155">{{ $s['nilaiAkhir'] }}</text>
            @endif
        @endforeach

        {{-- Titik yang sedang disorot, satu untuk tiap seri. --}}
        <template x-if="aktif !== null">
            <g>
                <template x-for="(s, i) in seri" :key="i">
                    <circle :cx="titik[aktif].x" :cy="seriY[i][aktif]" r="4"
                            :fill="s.warna" stroke="{{ Grafik::PERMUKAAN }}" stroke-width="2" />
                </template>
            </g>
        </template>
    </svg>

    {{-- Tooltip: nilainya memimpin, nama serinya menyusul. --}}
    <div x-show="aktif !== null" x-cloak
         class="pointer-events-none absolute left-1/2 top-0 z-10 -translate-x-1/2 rounded-lg bg-slate-900 px-3 py-2 text-xs text-white shadow-lg">
        <p class="mb-1 font-medium text-slate-300" x-text="aktif !== null ? titik[aktif].label : ''"></p>
        <template x-for="(s, i) in seri" :key="i">
            <p class="flex items-center gap-2 whitespace-nowrap">
                <svg width="12" height="4" aria-hidden="true" class="shrink-0">
                    <line x1="0" y1="2" x2="12" y2="2" :stroke="s.warna" stroke-width="2" stroke-linecap="round" />
                </svg>
                <span class="font-semibold" x-text="aktif !== null ? titik[aktif].nilai[i] : ''"></span>
                <span class="text-slate-400" x-text="s.nama"></span>
            </p>
        </template>
    </div>

    {{-- Setiap angka tetap terjangkau tanpa hover. --}}
    <details class="mt-3 text-xs">
        <summary class="cursor-pointer text-slate-500 hover:text-slate-700">Lihat sebagai tabel</summary>
        <div class="mt-2 overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-slate-500">
                    <tr>
                        <th class="py-1 pr-4 font-medium">Periode</th>
                        @foreach ($siap as $s)
                            <th class="py-1 pr-4 font-medium">{{ $s['nama'] }} ({{ $satuan }})</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach ($titikHover as $baris)
                        <tr>
                            <td class="py-1 pr-4">{{ $baris['label'] }}</td>
                            @foreach ($baris['nilai'] as $nilai)
                                <td class="py-1 pr-4" style="font-variant-numeric: tabular-nums">{{ $nilai }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</div>
