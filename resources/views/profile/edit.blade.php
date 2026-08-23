<x-layouts::app :title="'Profil Saya'">
    <x-page-header title="Profil Saya" subtitle="Kelola data akun dan kata sandi Anda." />

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Data Akun">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="Foto profil"
                             class="size-16 rounded-full object-cover ring-1 ring-slate-200">
                    @else
                        <span class="grid size-16 place-items-center rounded-full bg-emerald-600 text-xl font-semibold text-white">
                            {{ $user->initials }}
                        </span>
                    @endif

                    <div class="flex-1">
                        <x-input name="avatar" type="file" label="Foto Profil" accept="image/*"
                                 hint="JPG/PNG, maksimal 2 MB." />
                    </div>
                </div>

                @if ($user->avatar)
                    <p>
                        <a href="#" onclick="event.preventDefault(); this.closest('form').nextElementSibling.submit();"
                           class="text-xs text-rose-600 hover:underline">Hapus foto profil</a>
                    </p>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input name="name" label="Nama Lengkap" required :value="old('name', $user->name)" />
                    <x-input name="email" type="email" label="Email" required :value="old('email', $user->email)" />
                    <x-input name="phone" label="Nomor HP" :value="old('phone', $user->phone)" placeholder="08xxxxxxxxxx" />
                    <x-input name="username_display" label="Username" :value="$user->username" disabled
                             hint="Username hanya bisa diubah oleh administrator." />
                </div>

                <div class="flex justify-end">
                    <x-button type="submit">Simpan Perubahan</x-button>
                </div>
            </form>

            <form method="POST" action="{{ route('profile.avatar.destroy') }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </x-card>

        <div class="space-y-4">
            <x-card title="Keamanan">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">Role</dt>
                        <dd class="mt-0.5 flex flex-wrap gap-1">
                            @forelse ($user->roles as $role)
                                <x-badge color="emerald">{{ $role->name }}</x-badge>
                            @empty
                                <span class="text-slate-400">Belum ada role</span>
                            @endforelse
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Login terakhir</dt>
                        <dd class="mt-0.5 text-slate-800">
                            {{ $user->last_login_at?->translatedFormat('d F Y, H:i') ?? '—' }}
                            @if ($user->last_login_ip)
                                <span class="text-slate-400">({{ $user->last_login_ip }})</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-4 border-t border-slate-100 pt-4">
                    <x-button :href="route('password.change')" variant="secondary" size="sm" class="w-full">
                        Ganti Kata Sandi
                    </x-button>
                </div>
            </x-card>
        </div>
    </div>
</x-layouts::app>
