@php
    $typeBadge = [
        'route' => ['label' => 'route', 'color' => 'sky'],
        'url' => ['label' => 'url', 'color' => 'amber'],
        'header' => ['label' => 'header', 'color' => 'slate'],
        'divider' => ['label' => 'divider', 'color' => 'slate'],
    ][$menu->type];

    $routeMissing = $menu->type === 'route' && ! \Illuminate\Support\Facades\Route::has((string) $menu->route);
@endphp

<li data-menu-id="{{ $menu->id }}" wire:key="menu-{{ $menu->id }}">
    <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 hover:border-slate-300">
        <button type="button" data-handle
                class="cursor-grab text-slate-300 transition hover:text-slate-500 active:cursor-grabbing"
                title="Seret untuk memindahkan">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7 4a1 1 0 100 2 1 1 0 000-2zm6 0a1 1 0 100 2 1 1 0 000-2zM7 9a1 1 0 100 2 1 1 0 000-2zm6 0a1 1 0 100 2 1 1 0 000-2zM7 14a1 1 0 100 2 1 1 0 000-2zm6 0a1 1 0 100 2 1 1 0 000-2z" />
            </svg>
        </button>

        @if ($menu->icon)
            <x-icon :name="$menu->icon" class="size-4 shrink-0 text-slate-400" />
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="truncate text-sm font-medium {{ $menu->is_active ? 'text-slate-800' : 'text-slate-400 line-through' }}">
                    {{ $menu->title }}
                </span>
                <x-badge :color="$typeBadge['color']">{{ $typeBadge['label'] }}</x-badge>
                @if ($routeMissing)
                    <x-badge color="rose">route belum ada</x-badge>
                @endif
            </div>

            <p class="truncate text-xs text-slate-400">
                {{ $menu->route ?: $menu->url ?: '—' }}
                @if ($menu->permission)
                    &middot; {{ $menu->permission }}
                @endif
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            @can('menu.update')
                <button type="button" wire:click="toggleActive({{ $menu->id }})"
                        class="rounded px-2 py-1 text-xs text-slate-500 transition hover:bg-slate-100"
                        title="{{ $menu->is_active ? 'Sembunyikan' : 'Tampilkan' }}">
                    {{ $menu->is_active ? 'Sembunyikan' : 'Tampilkan' }}
                </button>
            @endcan

            @can('menu.create')
                <button type="button" wire:click="openCreate({{ $menu->id }})"
                        class="rounded px-2 py-1 text-xs text-emerald-700 transition hover:bg-emerald-50"
                        title="Tambah submenu">+ Sub</button>
            @endcan

            @can('menu.update')
                <button type="button" wire:click="openEdit({{ $menu->id }})"
                        class="rounded px-2 py-1 text-xs text-slate-600 transition hover:bg-slate-100">Ubah</button>
            @endcan

            @can('menu.delete')
                <button type="button" wire:click="delete({{ $menu->id }})"
                        wire:confirm="Hapus menu '{{ $menu->title }}'?"
                        class="rounded p-1 text-rose-600 transition hover:bg-rose-50" title="Hapus">
                    <x-icon name="trash" class="size-4" />
                </button>
            @endcan
        </div>
    </div>

    @if ($depth < 2)
        <ul data-list data-parent-id="{{ $menu->id }}" class="ml-6 mt-1 min-h-6 space-y-1 border-l border-dashed border-slate-200 pl-3">
            @foreach ($menu->children as $child)
                @include('livewire.partials.menu-node', ['menu' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
