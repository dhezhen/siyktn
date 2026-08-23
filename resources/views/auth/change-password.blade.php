<x-layouts::guest :title="'Ganti Kata Sandi'" :subtitle="'Kata sandi Anda perlu diperbarui'">
    <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <x-input name="current_password" type="password" label="Kata Sandi Saat Ini" required autofocus
                 autocomplete="current-password" />

        <x-input name="password" type="password" label="Kata Sandi Baru" required
                 autocomplete="new-password" hint="Minimal 8 karakter dan berbeda dari sebelumnya." />

        <x-input name="password_confirmation" type="password" label="Ulangi Kata Sandi Baru" required
                 autocomplete="new-password" />

        <x-button type="submit" class="w-full">Simpan</x-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-slate-500 hover:underline">Keluar</button>
    </form>
</x-layouts::guest>
