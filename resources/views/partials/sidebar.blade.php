@php($menuItems = \App\Support\Menu::items())

<aside x-cloak
       :class="{
           'translate-x-0': sidebarOpen,
           '-translate-x-full': !sidebarOpen,
           'lg:translate-x-0': desktopSidebarOpen,
           'lg:-translate-x-full': !desktopSidebarOpen
       }"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-emerald-950 dark:bg-slate-950 text-emerald-100/70 dark:text-slate-400 shadow-xl transition-transform duration-300 ease-out lg:shadow-none border-r border-transparent dark:border-slate-800">

    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 dark:border-slate-800 px-5">
        <img src="{{ asset('logo.png') }}" alt="{{ setting('app_name', config('app.name')) }}" class="size-9 shrink-0 object-contain drop-shadow-sm">
        <span class="truncate font-semibold text-white dark:text-slate-200">{{ setting('app_name', config('app.name')) }}</span>
        <button type="button" @click="sidebarOpen = false" aria-label="Tutup menu"
                class="ml-auto rounded-lg p-1.5 text-slate-400 transition duration-150 hover:bg-white/10 hover:text-white active:scale-90 lg:hidden">
            <x-icon name="x-mark" class="size-5" />
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @forelse ($menuItems as $item)
            @include('partials.menu-item', ['item' => $item])
        @empty
            <p class="px-2 py-3 text-xs text-slate-500">Belum ada menu yang bisa Anda akses.</p>
        @endforelse
    </nav>

    <div class="border-t border-white/10 dark:border-slate-800 px-5 py-3 text-xs text-emerald-300/60 dark:text-slate-500">
        Versi {{ config('app.version') }}
    </div>
</aside>
