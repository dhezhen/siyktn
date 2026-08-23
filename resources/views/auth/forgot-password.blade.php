<x-layouts::guest :title="'Lupa Kata Sandi'" :subtitle="'Kami akan mengirim tautan reset'">
    <p class="mb-4 text-sm text-slate-600">
        Masukkan email akun Anda. Bila terdaftar, kami kirimkan tautan untuk membuat kata sandi baru.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-input name="email" type="email" label="Email" required
                 :value="old('email')" autofocus placeholder="nama@contoh.id" />

        <x-button type="submit" class="w-full">Kirim Tautan Reset</x-button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="text-emerald-700 hover:underline">Kembali ke halaman masuk</a>
    </p>
</x-layouts::guest>
