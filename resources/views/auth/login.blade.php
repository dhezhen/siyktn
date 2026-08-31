<x-layouts::guest :title="'Masuk'" :subtitle="'Selamat datang kembali, silakan masuk ke akun Anda'">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <x-input name="login" label="Email atau Username" required
                 :value="old('login')" autofocus autocomplete="username"
                 placeholder="nama@contoh.id atau username" />

        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-semibold text-slate-800">
                    Kata Sandi <span class="text-rose-500">*</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-emerald-600 transition-colors hover:text-emerald-500 hover:underline">
                    Lupa sandi?
                </a>
            </div>
            <x-input name="password" type="password" required
                     autocomplete="current-password" placeholder="••••••••" />
        </div>

        <div class="flex items-center">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1"
                       class="size-4 rounded border-slate-300 text-emerald-600 transition-colors focus:ring-emerald-500">
                <span>Ingat saya</span>
            </label>
        </div>

        <x-button type="submit" class="w-full !py-2.5 text-base">Masuk ke Dasbor</x-button>
    </form>

    <div class="mt-8 border-t border-slate-200/60 pt-6 text-center">
        <p class="text-sm text-slate-500">
            Calon peserta baru?
            <a href="{{ route('pendaftaran.create') }}" class="font-semibold text-emerald-600 transition-colors hover:text-emerald-500 hover:underline">
                Daftar mandiri di sini
            </a>
        </p>
    </div>
</x-layouts::guest>
