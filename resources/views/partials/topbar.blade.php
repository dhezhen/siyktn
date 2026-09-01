<header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6">
    <!-- Tombol Sidebar Mobile -->
    <button type="button" @click="sidebarOpen = true" aria-label="Buka menu"
            class="-ml-1 rounded-lg p-2 text-slate-500 transition duration-150 hover:bg-slate-100 active:scale-90 lg:hidden">
        <x-icon name="bars" class="size-6" />
    </button>

    <!-- Tombol Sidebar Desktop -->
    <button type="button" @click="desktopSidebarOpen = !desktopSidebarOpen" aria-label="Buka/Tutup menu"
            class="-ml-1 hidden rounded-lg p-2 text-slate-500 transition duration-150 hover:bg-slate-100 active:scale-90 lg:block">
        <x-icon name="bars" class="size-6" />
    </button>

    <div class="flex-1"></div>

    @auth
        <div x-data="{ open: false }" class="relative">
            <button type="button" @click="open = ! open"
                    class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-700 transition duration-150 hover:bg-slate-100">
                <x-icon name="user-circle" class="size-6 text-slate-400" />
                <span class="hidden max-w-40 truncate sm:block">{{ auth()->user()->name }}</span>
                <x-icon name="chevron-down" class="size-4 text-slate-400 transition duration-200" x-bind:class="open \&\& 'rotate-180'" />
            </button>

            <div x-show="open" x-cloak @click.outside="open = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-56 origin-top-right overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                <div class="border-b border-slate-100 px-4 py-2">
                    <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->roles->pluck('name')->join(', ') ?: 'Tanpa role' }}</p>
                </div>

                @if (Route::has('profile.edit'))
                    <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 transition duration-100 hover:bg-slate-50">
                        <x-icon name="user-circle" class="size-4 text-slate-400" /> Profil Saya
                    </a>
                @endif

                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-rose-600 transition duration-100 hover:bg-rose-50">
                            <x-icon name="logout" class="size-4" /> Keluar
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endauth
</header>
