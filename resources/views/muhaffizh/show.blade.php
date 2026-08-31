<x-layouts::app :title="$muhaffizh->nama">
    <x-page-header :title="$muhaffizh->nama"
                   :subtitle="$muhaffizh->kode.' · '.$muhaffizh->jenis_kelamin_label">
        <x-slot:actions>
            <x-button :href="route('muhaffizh.index')" variant="secondary">Kembali</x-button>
            @can('muhaffizh.update')
                <x-button :href="route('muhaffizh.edit', $muhaffizh)">Ubah Data</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Halaqah Diampu', 'value' => $halaqah->count(), 'icon' => 'book'],
            ['label' => 'Halaqah Aktif', 'value' => $halaqah->where('is_aktif', true)->count(), 'icon' => 'check-circle'],
            ['label' => 'Santri Dibina', 'value' => $muhaffizh->jumlah_binaan, 'icon' => 'users'],
            ['label' => 'Bergabung', 'value' => $muhaffizh->tanggal_bergabung?->translatedFormat('M Y') ?? '—', 'icon' => 'identification'],
        ] as $stat)
            <x-card padding="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $stat['value'] }}</p>
                    </div>
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-600">
                        <x-icon :name="$stat['icon']" class="size-5" />
                    </span>
                </div>
            </x-card>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2" padding="p-0" title="Halaqah yang Diampu"
                subtitle="Halaqah yang masih berjalan ditampilkan lebih dulu.">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Halaqah</th>
                            <th class="px-5 py-3 font-medium">Angkatan</th>
                            <th class="px-5 py-3 font-medium">Santri</th>
                            <th class="px-5 py-3 font-medium">Jadwal</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($halaqah as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    @can('halaqah.view')
                                        <a href="{{ route('halaqah.show', $item) }}"
                                           class="font-medium text-emerald-700 hover:underline">{{ $item->nama }}</a>
                                    @else
                                        <span class="font-medium text-slate-900">{{ $item->nama }}</span>
                                    @endcan
                                    <p class="text-xs text-slate-500">
                                        {{ $item->kode }} &middot; {{ $item->jenis_kelamin_label }}
                                    </p>
                                </td>

                                <td class="px-5 py-3 text-slate-700">
                                    {{ $item->angkatan?->nama ?? '—' }}
                                    <span class="block text-xs text-slate-500">{{ $item->angkatan?->kode }}</span>
                                </td>

                                <td class="px-5 py-3 text-slate-700">
                                    {{ $item->anggota_aktif_count }}
                                    @if ($item->kuota > 0)
                                        <span class="text-slate-400">/ {{ $item->kuota }}</span>
                                    @endif
                                </td>

                                <td class="px-5 py-3 text-xs text-slate-600">
                                    {{ $item->jadwal ?: '—' }}
                                    @if ($item->ruang)
                                        <span class="block text-slate-400">Ruang {{ $item->ruang }}</span>
                                    @endif
                                </td>

                                <td class="px-5 py-3">
                                    <x-badge :color="$item->is_aktif ? 'emerald' : 'slate'">
                                        {{ $item->is_aktif ? 'Berjalan' : 'Nonaktif' }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state icon="book" title="Belum mengampu halaqah"
                                                   message="Muhaffizh ini belum ditugaskan ke halaqah mana pun." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="space-y-4">
            <x-card>
                <div class="flex flex-col items-center text-center">
                    @if ($muhaffizh->foto_url)
                        <img src="{{ $muhaffizh->foto_url }}" alt="Foto {{ $muhaffizh->nama }}"
                             class="size-24 rounded-full object-cover ring-1 ring-slate-200">
                    @else
                        <span class="grid size-24 place-items-center rounded-full bg-slate-200 text-2xl font-semibold text-slate-500">
                            {{ $muhaffizh->initials }}
                        </span>
                    @endif

                    <p class="mt-3 font-medium text-slate-900">{{ $muhaffizh->nama }}</p>
                    <p class="font-mono text-xs text-slate-500">{{ $muhaffizh->kode }}</p>

                    <div class="mt-2">
                        <x-badge :color="$muhaffizh->status_color">{{ ucfirst($muhaffizh->status) }}</x-badge>
                    </div>
                </div>
            </x-card>

            <x-card title="Informasi">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'Nomor HP' => $muhaffizh->no_hp,
                        'Email' => $muhaffizh->email,
                        'Pendidikan' => $muhaffizh->pendidikan,
                        'Sanad / Riwayat' => $muhaffizh->sanad_riwayat,
                        'Tanggal Bergabung' => $muhaffizh->tanggal_bergabung?->translatedFormat('d F Y'),
                    ] as $label => $value)
                        <div>
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="mt-0.5 text-slate-800">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach

                    @if ($muhaffizh->keterangan)
                        <div>
                            <dt class="text-slate-500">Keterangan</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-slate-800">{{ $muhaffizh->keterangan }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <x-card title="Akun Login">
                @if ($muhaffizh->user)
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-200 text-xs font-semibold text-slate-500">
                            {{ $muhaffizh->user->initials }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $muhaffizh->user->name }}</p>
                            <p class="truncate font-mono text-xs text-slate-500">{{ $muhaffizh->user->username }}</p>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1">
                        @forelse ($muhaffizh->user->roles as $role)
                            <x-badge color="sky">{{ $role->name }}</x-badge>
                        @empty
                            <x-badge color="amber">tanpa role</x-badge>
                        @endforelse

                        @unless ($muhaffizh->user->is_active)
                            <x-badge color="rose">akun nonaktif</x-badge>
                        @endunless
                    </div>

                    @can('user.update')
                        <x-button :href="route('user.edit', $muhaffizh->user)" variant="secondary" size="sm"
                                  class="mt-3 w-full">
                            Kelola Akun
                        </x-button>
                        <p class="mt-2 text-center text-xs text-slate-400">
                            Reset kata sandi dilakukan dari halaman Pengguna.
                        </p>
                    @endcan
                @else
                    <p class="text-sm text-slate-500">
                        Belum punya akun. Muhaffizh tanpa akun tetap bisa didata dan mengampu halaqah —
                        akun hanya diperlukan bila ia ingin masuk sendiri ke sistem.
                    </p>

                    @if (auth()->user()->can('muhaffizh.update') && auth()->user()->can('user.create'))
                        @if ($muhaffizh->email)
                            <form method="POST" action="{{ route('muhaffizh.akun', $muhaffizh) }}" class="mt-3">
                                @csrf
                                <x-button type="submit" icon="key" class="w-full">Buatkan Akun</x-button>
                            </form>
                            <p class="mt-2 text-xs leading-relaxed text-slate-500">
                                Akun dibuat memakai email <strong>{{ $muhaffizh->email }}</strong>, langsung
                                ber-role <strong>muhaffizh</strong>, dengan kata sandi sementara yang wajib
                                diganti saat login pertama.
                            </p>
                        @else
                            <div class="mt-3 rounded-lg bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">
                                Isi dulu email muhaffizh ini lewat <strong>Ubah Data</strong>. Email dipakai
                                untuk masuk dan memulihkan kata sandi.
                            </div>
                        @endif
                    @endif
                @endif
            </x-card>
        </div>
    </div>
</x-layouts::app>
