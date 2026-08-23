@php($editing = $user->exists)

<x-layouts::app :title="$editing ? 'Ubah Pengguna' : 'Tambah Pengguna'">
    <x-page-header :title="$editing ? 'Ubah Pengguna: '.$user->name : 'Tambah Pengguna'"
                   subtitle="Data akun, hak akses, dan status aktif." />

    <form method="POST" action="{{ $editing ? route('user.update', $user) : route('user.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <x-card title="Data Diri">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input name="name" label="Nama Lengkap" required :value="old('name', $user->name)" />
                        <x-input name="username" label="Username" required :value="old('username', $user->username)"
                                 hint="Dipakai untuk login. Huruf, angka, titik, garis bawah." />
                        <x-input name="email" type="email" label="Email" required :value="old('email', $user->email)" />
                        <x-input name="phone" label="Nomor HP" :value="old('phone', $user->phone)"
                                 placeholder="08xxxxxxxxxx" />
                    </div>

                    <div class="mt-4">
                        <x-input name="avatar" type="file" label="Foto Profil" accept="image/*"
                                 hint="Opsional. JPG/PNG, maksimal 2 MB." />
                    </div>
                </x-card>

                <x-card title="Kata Sandi"
                        :subtitle="$editing ? 'Kosongkan bila tidak ingin mengubah kata sandi.' : 'Kata sandi awal untuk pengguna ini.'">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input name="password" type="password" :label="$editing ? 'Kata Sandi Baru' : 'Kata Sandi'"
                                 :required="! $editing" autocomplete="new-password" hint="Minimal 8 karakter." />
                        <x-input name="password_confirmation" type="password" label="Ulangi Kata Sandi"
                                 :required="! $editing" autocomplete="new-password" />
                    </div>

                    <label class="mt-4 flex items-start gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="must_change_password" value="1"
                               @checked(old('must_change_password', $user->must_change_password))
                               class="mt-0.5 size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>Wajib ganti kata sandi saat login berikutnya</span>
                    </label>

                    @if ($editing)
                        @can('user.reset-password')
                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <p class="mb-2 text-xs text-slate-500">
                                    Atau buat kata sandi acak dan paksa pengguna menggantinya saat login.
                                </p>
                                <x-button variant="secondary" size="sm"
                                          onclick="document.getElementById('reset-password-form').submit()">
                                    Reset Kata Sandi
                                </x-button>
                            </div>
                        @endcan
                    @endif
                </x-card>
            </div>

            <div class="space-y-4">
                <x-card title="Role">
                    @if ($editing && $user->id === auth()->id())
                        <p class="mb-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">
                            Role akun sendiri tidak bisa diubah dari halaman ini, untuk mencegah Anda
                            mengunci diri sendiri.
                        </p>
                    @endif

                    <div class="space-y-2">
                        @foreach ($roles as $role)
                            @php($isSuper = $role->name === config('permission.super_admin_role'))
                            @continue($isSuper && ! auth()->user()->is_super_admin)

                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                       @checked(in_array($role->name, old('roles', $assigned), true))
                                       @disabled($editing && $user->id === auth()->id())
                                       class="mt-0.5 size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span>
                                    {{ $role->name }}
                                    @if (config('rbac.roles.'.$role->name.'.description'))
                                        <span class="block text-xs text-slate-500">
                                            {{ config('rbac.roles.'.$role->name.'.description') }}
                                        </span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Status">
                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $user->is_active ?? true))
                               @disabled($editing && $user->id === auth()->id())
                               class="mt-0.5 size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>
                            Akun aktif
                            <span class="block text-xs text-slate-500">
                                Akun nonaktif tidak bisa masuk, tapi datanya tetap tersimpan.
                            </span>
                        </span>
                    </label>
                </x-card>
            </div>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <x-button :href="route('user.index')" variant="secondary">Batal</x-button>
            <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</x-button>
        </div>
    </form>

    @if ($editing)
        @can('user.reset-password')
            <form id="reset-password-form" method="POST" action="{{ route('user.reset-password', $user) }}" class="hidden">
                @csrf
                @method('PUT')
            </form>
        @endcan
    @endif
</x-layouts::app>
