@props(['title' => 'Belum ada data', 'message' => null, 'icon' => 'info'])

<div class="flex flex-col items-center justify-center px-4 py-12 text-center">
    <span class="mb-3 grid size-12 place-items-center rounded-full bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <p class="font-medium text-slate-700">{{ $title }}</p>
    @if ($message)<p class="mt-1 max-w-sm text-sm text-slate-500">{{ $message }}</p>@endif
    @isset($actions)<div class="mt-4">{{ $actions }}</div>@endisset
</div>
