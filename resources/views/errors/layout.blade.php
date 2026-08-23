@php
    $loggedIn = auth()->check();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} — {{ setting('app_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full items-center justify-center bg-slate-100 p-4 antialiased">
    <div class="w-full max-w-md text-center">
        <p class="text-6xl font-bold text-slate-300">{{ $code }}</p>
        <h1 class="mt-4 text-xl font-semibold text-slate-900">{{ $title }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $message }}</p>

        <div class="mt-6 flex justify-center gap-2">
            <a href="{{ $loggedIn ? route('dashboard') : route('login') }}"
               class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                {{ $loggedIn ? 'Kembali ke Dashboard' : 'Ke Halaman Masuk' }}
            </a>
            <button type="button" onclick="history.back()"
                    class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-50">
                Halaman Sebelumnya
            </button>
        </div>
    </div>
</body>
</html>
