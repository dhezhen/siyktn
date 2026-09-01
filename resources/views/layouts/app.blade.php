<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sistem Informasi Karantina Tahfizh">

    <title>{{ isset($title) ? $title.' — '.setting('app_name', config('app.name')) : setting('app_name', config('app.name')) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    @livewireStyles
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">

<div x-data="{ sidebarOpen: false, desktopSidebarOpen: localStorage.getItem('desktopSidebarOpen') !== 'false' }" 
     x-init="$watch('desktopSidebarOpen', value => localStorage.setItem('desktopSidebarOpen', value))"
     class="min-h-full bg-slate-100">

    <div x-show="sidebarOpen" x-transition.opacity x-cloak
         @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

    @include('partials.sidebar')

    <div :class="desktopSidebarOpen ? 'lg:pl-64' : 'lg:pl-0'" class="transition-all duration-300">
        @include('partials.topbar')

        <main class="p-4 sm:p-6">
            @isset($header)
                {{ $header }}
            @endisset

            <x-alert />

            {{ $slot }}
        </main>
    </div>

    {{-- Notifikasi mengambang untuk seluruh komponen Livewire. --}}
    <x-toast />
</div>

@livewireScripts

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.addEventListener('swal', function (e) {
            const detail = e.detail || (e.detail && e.detail[0]) ? (e.detail[0] || e.detail) : {};
            Swal.fire({
                icon: detail.icon || 'success',
                title: detail.title || 'Berhasil!',
                text: detail.text || detail.message || '',
                timer: detail.timer || 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: detail.toast !== undefined ? detail.toast : true,
                position: detail.position || 'top-end',
                confirmButtonColor: '#059669',
            });
        });

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}",
                confirmButtonColor: '#059669'
            });
        @endif
    });
</script>
</body>
</html>
