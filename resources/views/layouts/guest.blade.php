<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ isset($title) ? $title.' — '.setting('app_name', config('app.name')) : setting('app_name', config('app.name')) }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex h-full items-center justify-center bg-slate-100 p-4 antialiased">

<div class="w-full max-w-md">
    <div class="mb-6 text-center">
        <div class="mx-auto mb-3 grid size-14 place-items-center rounded-xl bg-emerald-600 text-2xl font-bold text-white">
            {{ Str::substr(setting('app_name', config('app.name')), 0, 1) }}
        </div>
        <h1 class="text-lg font-semibold text-slate-900">{{ setting('app_name', config('app.name')) }}</h1>
        @isset($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endisset
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <x-alert />
        {{ $slot }}
    </div>

    <p class="mt-6 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} {{ setting('app_name', config('app.name')) }}
    </p>
</div>

@livewireScripts
</body>
</html>
