<x-layouts::app :title="$peserta->nama">
    @php($terakhir = $peserta->pendaftaran->first())

    <div x-data="{ zoomImage: null, zoomTitle: '' }">
        <x-page-header :title="$peserta->nama"
                       :subtitle="($peserta->nik ? 'NIK '.$peserta->nik : 'NIK belum diisi').' · '.$peserta->pendaftaran->count().' kali mendaftar'">
            <x-slot:actions>
                <x-button :href="route('peserta.index')" variant="secondary">Kembali</x-button>
                @can('peserta.update')
                    <x-button :href="route('peserta.edit', $peserta)">Ubah Data</x-button>
                @endcan
            </x-slot:actions>
        </x-page-header>

        @unless ($peserta->boleh_mendaftar_lagi)
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-5 py-4">
                <x-icon name="warning" class="mt-0.5 size-5 shrink-0 text-rose-600" />
                <div>
                    <p class="text-sm font-medium text-rose-900">Peserta ini tidak boleh mendaftar lagi</p>
                    <p class="text-xs text-rose-800">{{ $peserta->alasan_cekal ?: 'Tidak ada keterangan.' }}</p>
                </div>
            </div>
        @endunless

        @if ($terakhir?->isMenunggu())
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
                <div class="flex items-start gap-3">
                    <x-icon name="warning" class="mt-0.5 size-5 shrink-0 text-amber-600" />
                    <div>
                        <p class="text-sm font-medium text-amber-900">Pendaftaran terbaru menunggu verifikasi</p>
                        <p class="text-xs text-amber-800">Nomor induk baru diberikan setelah pendaftaran disetujui.</p>
                    </div>
                </div>
                @can('peserta.approve')
                    <x-button :href="route('pendaftaran.index')" size="sm">Tinjau di Halaman Pendaftaran</x-button>
                @endcan
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <x-card title="Data Pribadi">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            'NIK' => $peserta->nik,
                            'Nama Lengkap' => $peserta->nama,
                            'Jenis Kelamin' => $peserta->jenis_kelamin_label,
                            'Kewarganegaraan' => $peserta->kewarganegaraan === 'WNA' ? 'WNA (Luar Negeri)' : 'WNI (Indonesia)',
                            'Negara' => $peserta->negara ?: 'Indonesia',
                            'Provinsi' => $peserta->provinsi ?: '—',
                            'Kabupaten / Kota' => $peserta->kabupaten_kota ?: '—',
                            'Email' => $peserta->email,
                            'Tempat, Tanggal Lahir' => trim(($peserta->tempat_lahir ?? '').($peserta->tanggal_lahir ? ', '.$peserta->tanggal_lahir->translatedFormat('d F Y') : ''), ', '),
                            'Nomor HP' => $peserta->no_hp,
                        ] as $label => $value)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                                <dd class="mt-0.5 text-sm text-slate-800">{{ $value ?: '—' }}</dd>
                            </div>
                        @endforeach

                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Alamat Lengkap</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-sm text-slate-800">{{ $peserta->alamat_lengkap_formatted ?: '—' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 border-t border-slate-100 pt-4">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Data Wali</h3>
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">Nama Wali</dt>
                                <dd class="mt-0.5 text-sm text-slate-800">{{ $peserta->nama_wali ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">Nomor HP Wali</dt>
                                <dd class="mt-0.5 text-sm text-slate-800">{{ $peserta->no_hp_wali ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </x-card>

                {{-- Riwayat keikutsertaan --}}
                <x-card padding="p-0" title="Riwayat Pendaftaran"
                        subtitle="Setiap angkatan yang pernah diikuti, terbaru di atas.">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Angkatan</th>
                                    <th class="px-5 py-3 font-medium">Kode / Nomor Induk</th>
                                    <th class="px-5 py-3 font-medium">Didaftarkan</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($peserta->pendaftaran as $daftar)
                                    <tr class="align-top hover:bg-slate-50">
                                        <td class="px-5 py-3">
                                            @if ($daftar->angkatan)
                                                <a href="{{ route('angkatan.show', $daftar->angkatan) }}"
                                                   class="font-medium text-emerald-700 hover:underline">
                                                    {{ $daftar->angkatan->nama }}
                                                </a>
                                                <p class="text-xs text-slate-500">{{ $daftar->angkatan->tahun }}</p>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>

                                        <td class="px-5 py-3">
                                            <p class="font-mono text-xs text-slate-600">{{ $daftar->kode_pendaftaran }}</p>
                                            <p class="font-mono text-xs text-slate-800">{{ $daftar->nomor_induk ?: 'belum ada' }}</p>
                                        </td>

                                        <td class="px-5 py-3 text-xs text-slate-600">
                                            {{ $daftar->didaftarkan_pada?->translatedFormat('d M Y') ?? '—' }}
                                            <span class="block text-slate-400">
                                                {{ $daftar->sumber_pendaftaran === 'mandiri' ? 'mandiri' : 'petugas' }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-3">
                                            <div class="flex flex-col items-start gap-1">
                                                <x-badge :color="$daftar->status_pendaftaran_color">
                                                    {{ $daftar->status_pendaftaran_label }}
                                                </x-badge>
                                                @if ($daftar->status_pendaftaran === 'disetujui')
                                                    <x-badge :color="$daftar->status_color">{{ $daftar->status_label }}</x-badge>
                                                @endif
                                            </div>

                                            @if ($daftar->alasan_penolakan)
                                                <p class="mt-1.5 max-w-xs text-xs text-rose-700">{{ $daftar->alasan_penolakan }}</p>
                                            @endif

                                            @if ($daftar->ditinjau_pada)
                                                <p class="mt-1 text-xs text-slate-400">
                                                    oleh {{ $daftar->peninjau?->name ?? 'petugas' }}
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <x-empty-state title="Belum pernah mendaftar"
                                                           message="Peserta ini belum terhubung ke angkatan mana pun." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            <div class="space-y-4">
                <x-card>
                    <div class="flex flex-col items-center text-center">
                        @if ($peserta->foto_url)
                            <button type="button"
                                    @click="zoomImage = '{{ $peserta->foto_url }}'; zoomTitle = 'Foto Profil - {{ addslashes($peserta->nama) }}'"
                                    title="Klik untuk Auto-Zoom Foto Profil"
                                    class="group relative block size-24 overflow-hidden rounded-full ring-2 ring-emerald-500/30 transition-all hover:scale-105 hover:ring-emerald-600 focus:outline-none shadow-md">
                                <img src="{{ $peserta->foto_url }}" alt="Foto {{ $peserta->nama }}" class="size-full object-cover">
                                <span class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs font-bold gap-1">
                                    <x-icon name="search" class="size-4" /> Auto Zoom
                                </span>
                            </button>
                        @else
                            <span class="grid size-24 place-items-center rounded-full bg-slate-200 text-2xl font-semibold text-slate-500">
                                {{ $peserta->initials }}
                            </span>
                        @endif

                        <p class="mt-3 font-bold text-slate-900 text-base">{{ $peserta->nama }}</p>
                        <p class="font-mono text-xs text-slate-500">{{ $terakhir?->nomor_induk ?: '—' }}</p>

                        <div class="mt-2 flex flex-wrap justify-center gap-1">
                            @if ($peserta->isAlumni())
                                <x-badge color="sky">Alumni</x-badge>
                            @endif
                            @if ($peserta->pendaftaran->count() > 1)
                                <x-badge color="amber">{{ $peserta->pendaftaran->count() }}x mendaftar</x-badge>
                            @endif
                        </div>
                    </div>
                </x-card>

                <x-card title="Berkas KTP/KK (Kartu Tanda Penduduk / Kartu Keluarga)">
                    @if ($peserta->ktp_path)
                        <div class="space-y-3">
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-900/5 p-2 text-center">
                                @php($ext = strtolower(pathinfo($peserta->ktp_path, PATHINFO_EXTENSION)))
                                @if ($ext === 'pdf')
                                    <iframe src="{{ route('pendaftaran.ktp', $peserta) }}" class="h-64 w-full rounded-lg border border-slate-200"></iframe>
                                @else
                                    <button type="button"
                                            @click="zoomImage = '{{ route('pendaftaran.ktp', $peserta) }}'; zoomTitle = 'Dokumen KTP/KK - {{ addslashes($peserta->nama) }}'"
                                            title="Klik untuk Auto-Zoom Dokumen KTP/KK"
                                            class="group relative block w-full">
                                        <img src="{{ route('pendaftaran.ktp', $peserta) }}" alt="Pratinjau KTP/KK {{ $peserta->nama }}"
                                             class="max-h-72 w-full rounded-lg object-contain transition-transform duration-200 hover:scale-[1.02] bg-white shadow-sm">
                                        <span class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs font-bold rounded-lg gap-1">
                                            <x-icon name="search" class="size-4" /> Auto Zoom KTP/KK
                                        </span>
                                    </button>
                                @endif
                            </div>

                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs text-emerald-900 shadow-sm">
                                <div class="flex items-center gap-1.5 font-bold text-emerald-950">
                                    <x-icon name="check-badge" class="size-4 text-emerald-700" />
                                    <span>NIK Terverifikasi Sistem:</span>
                                </div>
                                <p class="mt-0.5 font-mono text-base font-extrabold text-emerald-950">{{ $peserta->nik ?: 'Belum diisi' }}</p>
                                <p class="mt-1 text-[11px] text-emerald-800">Cocokkan NIK di atas dengan foto KTP/KK pada pratinjau.</p>
                            </div>

                            <div class="flex gap-2">
                                <button type="button"
                                        @click="zoomImage = '{{ route('pendaftaran.ktp', $peserta) }}'; zoomTitle = 'Dokumen KTP/KK - {{ addslashes($peserta->nama) }}'"
                                        class="flex-1 rounded-xl bg-emerald-700 py-2 text-xs font-bold text-white shadow hover:bg-emerald-800 inline-flex items-center justify-center gap-1.5">
                                    <x-icon name="search" class="size-3.5" /> Auto Zoom
                                </button>
                                <x-button :href="route('pendaftaran.ktp', $peserta)" variant="secondary" size="sm"
                                          target="_blank" class="flex-1">
                                    <span>Tab Baru</span>
                                    <x-icon name="arrow-top-right-on-square" class="size-3.5 ml-1 inline" />
                                </x-button>
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center text-xs text-amber-900">
                            <div class="flex items-center justify-center gap-1.5 font-bold text-amber-950">
                                <x-icon name="exclamation-triangle" class="size-4 text-amber-700" />
                                <span>Tidak Ada Berkas KTP/KK</span>
                            </div>
                            <p class="mt-1 text-amber-800">Peserta ini belum mengunggah dokumen KTP/KK.</p>
                        </div>
                    @endif
                </x-card>
            </div>
        </div>

        {{-- Modal Auto-Zoom Popup Lightbox --}}
        <template x-teleport="body">
            <div x-show="zoomImage" x-cloak @keydown.escape.window="zoomImage = null"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <!-- Backdrop Overlay -->
                <div x-show="zoomImage" @click="zoomImage = null"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"></div>

                <!-- Modal Body Container -->
                <div x-show="zoomImage"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-90"
                     class="relative z-10 max-w-4xl w-full rounded-2xl bg-white p-5 shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                        <h3 class="font-bold text-slate-900 text-sm" x-text="zoomTitle || 'Pratinjau Foto'"></h3>
                        <button type="button" @click="zoomImage = null" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                            <x-icon name="x-mark" class="size-5" />
                        </button>
                    </div>
                    <div class="overflow-auto max-h-[75vh] grid place-items-center bg-slate-950/5 rounded-xl p-3">
                        <img :src="zoomImage" :alt="zoomTitle" class="max-h-[70vh] w-auto object-contain rounded-lg shadow-lg">
                    </div>
                    <div class="mt-3 flex justify-between items-center text-xs text-slate-500 px-1">
                        <span class="inline-flex items-center gap-1">
                            <x-icon name="info" class="size-3.5 text-slate-400" /> Tekan ESC atau klik luar foto untuk menutup
                        </span>
                        <a :href="zoomImage" target="_blank" class="font-bold text-emerald-700 hover:underline inline-flex items-center gap-1">
                            <span>Buka Ukuran Asli</span>
                            <x-icon name="arrow-top-right-on-square" class="size-3.5" />
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts::app>

