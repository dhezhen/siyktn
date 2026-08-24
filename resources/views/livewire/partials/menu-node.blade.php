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
    <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 transition duration-150 ease-out hover:border-slate-300 hover:shadow-sm">
        <button type="button" data-handle aria-label="Seret untuk memindahkan"
                class="cursor-grab rounded p-1 text-slate-300 transition duration-150 hover:bg-slate-100 hover:text-slate-500 active:cursor-grabbing">
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

        <div class="flex shrink-0 items-center gap-0.5">
            @can('menu.update')
                <x-icon-button
                    :icon="$menu->is_active ? 'eye-slash' : 'eye'"
                    :label="$menu->is_active ? 'Sembunyikan dari sidebar' : 'Tampilkan di sidebar'"
                    :variant="$menu->is_active ? 'default' : 'primary'"
                    wire:click="toggleActive({{ $menu->id }})"
                    wire:loading.attr="disabled" wire:target="toggleActive({{ $menu->id }})" />
            @endcan

            @can('menu.create')
                <x-icon-button icon="plus" label="Tambah submenu" variant="primary"
                               wire:click="openCreate({{ $menu->id }})" />
            @endcan

            @can('menu.update')
                <x-icon-button icon="pencil" label="Ubah menu"
                               wire:click="openEdit({{ $menu->id }})" />
            @endcan

            @can('menu.delete')
                <x-icon-button icon="trash" label="Hapus menu" variant="danger"
                               wire:click="delete({{ $menu->id }})"
                               wire:confirm="Hapus menu '{{ $menu->title }}'?" />
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
