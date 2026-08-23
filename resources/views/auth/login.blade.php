<x-layouts::guest :title="'Masuk'" :subtitle="'Silakan masuk untuk melanjutkan'">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <x-input name="login" label="Email atau Username" required
                 :value="old('login')" autofocus autocomplete="username"
                 placeholder="nama@contoh.id atau username" />

        <x-input name="password" type="password" label="Kata Sandi" required
                 autocomplete="current-password" placeholder="••••••••" />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1"
                       class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                Ingat saya
            </label>

            <a href="{{ route('password.request') }}" class="text-sm text-emerald-700 hover:underline">
                Lupa kata sandi?
            </a>
        </div>

        <x-button type="submit" class="w-full">Masuk</x-button>
    </form>
</x-layouts::guest>
