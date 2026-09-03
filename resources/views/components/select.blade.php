@props(['label' => null, 'name', 'hint' => null, 'required' => false])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    <select name="{{ $name }}" id="{{ $name }}"
        {{ $attributes->merge([
            'class' => 'block w-full rounded-xl border-0 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-800 shadow-sm ring-1 ring-inset ring-slate-300 dark:ring-slate-700 focus:ring-2 focus:ring-inset focus:ring-emerald-600 dark:focus:ring-emerald-400 transition-all duration-200'
                . ($errors->has($name) ? ' ring-rose-400 dark:ring-rose-500 focus:ring-rose-500' : ''),
        ]) }}>
        {{ $slot }}
    </select>

    @error($name)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    @if ($hint && ! $errors->has($name))<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>@endif
</div>
