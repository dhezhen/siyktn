@php($menuItems = \App\Support\Menu::items())

<aside x-cloak
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0">

    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-emerald-600 font-bold text-white">
            {{ Str::substr(setting('app_name', config('app.name')), 0, 1) }}
        </span>
        <span class="truncate font-semibold text-white">{{ setting('app_name', config('app.name')) }}</span>
        <button type="button" @click="sidebarOpen = false"
                class="ml-auto rounded p-1 text-slate-400 hover:text-white lg:hidden">
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

    <div class="border-t border-white/10 px-5 py-3 text-xs text-slate-500">
        Versi {{ config('app.version') }}
    </div>
</aside>
