@php
    $type = $item['type'] ?? 'route';
    $children = $item['children'] ?? [];
    $active = \App\Support\Menu::isActive($item);
@endphp

@if ($type === 'header')
    <p class="px-3 pt-5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
        {{ $item['title'] }}
    </p>

@elseif ($type === 'divider')
    <hr class="my-3 border-white/10">

@elseif ($children !== [])
    <div x-data="{ open: @js($active) }">
        <button type="button" @click="open = ! open"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-white/5 hover:text-white {{ $active ? 'text-white' : '' }}">
            <x-icon :name="$item['icon'] ?? 'dot'" class="size-5 shrink-0" />
            <span class="flex-1 truncate text-left">{{ $item['title'] }}</span>
            <svg class="size-4 shrink-0 transition-transform" x-bind:class="open && 'rotate-180'"
                 fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <div x-show="open" x-collapse class="mt-1 space-y-1 pl-5">
            @foreach ($children as $child)
                @include('partials.menu-item', ['item' => $child])
            @endforeach
        </div>
    </div>

@else
    <a href="{{ \App\Support\Menu::url($item) }}" target="{{ $item['target'] ?? '_self' }}"
       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $active ? 'bg-emerald-600/15 text-emerald-400' : 'hover:bg-white/5 hover:text-white' }}">
        <x-icon :name="$item['icon'] ?? 'dot'" class="size-5 shrink-0" />
        <span class="truncate">{{ $item['title'] }}</span>
    </a>
@endif
