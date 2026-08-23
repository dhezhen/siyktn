<x-layouts::guest :title="'Reset Kata Sandi'" :subtitle="'Buat kata sandi baru'">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-input name="email" type="email" label="Email" required :value="old('email', $email)" readonly />

        <x-input name="password" type="password" label="Kata Sandi Baru" required
                 autocomplete="new-password" hint="Minimal 8 karakter." />

        <x-input name="password_confirmation" type="password" label="Ulangi Kata Sandi Baru" required
                 autocomplete="new-password" />

        <x-button type="submit" class="w-full">Simpan Kata Sandi</x-button>
    </form>
</x-layouts::guest>
