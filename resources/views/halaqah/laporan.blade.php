<x-layouts::app title="Laporan & Syahadah">
    <x-page-header title="Laporan & Syahadah"
                   :subtitle="$halaqah->nama.' · '.$halaqah->kode.' · '.($halaqah->angkatan?->nama ?? '')">
        <x-slot:actions>
            <x-button :href="route('halaqah.show', $halaqah)" variant="secondary" icon="arrow-left">Kembali</x-button>
            <x-button :href="route('halaqah.laporan.export', $halaqah)" variant="secondary" icon="document-arrow-down" external>Ekspor CSV</x-button>
            <x-button variant="primary" icon="printer" onclick="window.print()">Cetak</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mt-6" padding="none">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Santri</th>
                        <th scope="col" class="px-5 py-3 font-medium">TTL</th>
                        <th scope="col" class="px-5 py-3 font-medium">Hafalan Ziyadah</th>
                        <th scope="col" class="px-5 py-3 font-medium">Rata-rata Skor</th>
                        <th scope="col" class="px-5 py-3 font-medium">Predikat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($anggota as $item)
                        @php
                            $peserta = $item->pendaftaran?->peserta;
                            $ziyadah = (float) ($item->ziyadah_halaman ?? 0);
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $peserta?->nama ?? '—' }}</p>
                                <p class="text-xs text-slate-500">NI/Syahadah: {{ $item->pendaftaran?->nomor_induk ?: '—' }}</p>
                            </td>
                            <td class="px-5 py-3">
                                {{ $peserta?->tempat_lahir ?? '—' }}, {{ $peserta?->tanggal_lahir?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">
                                    {{ rtrim(rtrim(number_format($ziyadah, 1, ',', '.'), '0'), ',') }} Halaman
                                </p>
                                <p class="text-xs text-slate-500">
                                    Setara {{ \App\Models\Setoran::setaraJuz($ziyadah) }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="font-medium text-slate-800">{{ number_format($item->rata_rata_skor, 2, ',', '.') }}</span>
                                <span class="text-xs text-slate-500">/ 4.00</span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($item->predikat === 'Mumtaz')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Mumtaz</span>
                                @elseif ($item->predikat === 'Jayyid Jiddan')
                                    <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">Jayyid Jiddan</span>
                                @elseif ($item->predikat === 'Jayyid')
                                    <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700">Jayyid</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                                <x-icon name="users" class="mx-auto mb-2 size-8 text-slate-300" />
                                <p>Belum ada santri aktif di halaqah ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts::app>
