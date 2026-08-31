<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ isset($title) ? $title.' — '.setting('app_name', config('app.name')) : setting('app_name', config('app.name')) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex h-full items-center justify-center bg-slate-50 p-4 antialiased selection:bg-emerald-100 selection:text-emerald-900">

<!-- Latar Belakang Pola Titik -->
<div class="fixed inset-0 -z-10 h-full w-full bg-slate-50 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:20px_20px] [mask-image:radial-gradient(ellipse_60%_60%_at_50%_50%,#000_60%,transparent_100%)]"></div>

<div class="relative w-full max-w-[420px]">
    <!-- Efek Cahaya (Glow) di belakang card -->
    <div class="absolute -inset-1 -z-10 rounded-[2rem] bg-gradient-to-br from-emerald-200 via-transparent to-teal-200 opacity-40 blur-2xl"></div>

    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-transform duration-300 hover:scale-105">
            <img src="{{ asset('logo.png') }}" alt="{{ setting('app_name', config('app.name')) }}" class="h-10 w-auto drop-shadow-sm">
        </div>
        <h1 class="text-xl font-bold leading-snug tracking-tight text-slate-900">{{ setting('app_name', config('app.name')) }}</h1>
        @isset($subtitle)
            <p class="mt-2 text-sm text-slate-500">{{ $subtitle }}</p>
        @endisset
    </div>

    <div class="rounded-2xl border border-white bg-white/80 p-6 shadow-xl shadow-slate-200/50 backdrop-blur-xl sm:p-8">
        <x-alert />
        {{ $slot }}
    </div>

    <p class="mt-8 text-center text-sm font-medium text-slate-400">
        &copy; {{ date('Y') }} {{ setting('app_name', config('app.name')) }}
    </p>
</div>

@livewireScripts
</body>
</html>
