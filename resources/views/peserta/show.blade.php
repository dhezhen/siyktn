<x-layouts::app :title="$peserta->nama">
    @php($terakhir = $peserta->pendaftaran->first())

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
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Alamat</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-slate-800">{{ $peserta->alamat ?: '—' }}</dd>
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
                        <img src="{{ $peserta->foto_url }}" alt="Foto {{ $peserta->nama }}"
                             class="size-24 rounded-full object-cover ring-1 ring-slate-200">
                    @else
                        <span class="grid size-24 place-items-center rounded-full bg-slate-200 text-2xl font-semibold text-slate-500">
                            {{ $peserta->initials }}
                        </span>
                    @endif

                    <p class="mt-3 font-medium text-slate-900">{{ $peserta->nama }}</p>
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

            <x-card title="Berkas KTP">
                @if ($peserta->ktp_path)
                    <x-button :href="route('pendaftaran.ktp', $peserta)" variant="secondary" size="sm"
                              target="_blank" class="w-full">
                        Lihat Berkas KTP
                    </x-button>
                    <p class="mt-2 text-center text-xs text-slate-400">
                        Berkas tertutup, hanya untuk petugas berwenang.
                    </p>
                @else
                    <p class="text-center text-xs text-slate-400">Tidak ada berkas KTP terlampir.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-layouts::app>
