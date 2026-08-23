@props(['label' => null, 'name', 'type' => 'text', 'hint' => null, 'required' => false])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}"
        {{ $attributes->merge([
            'class' => 'block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600'
                . ($errors->has($name) ? ' ring-rose-400 focus:ring-rose-500' : ''),
        ]) }}>

    @error($name)
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror

    @if ($hint && ! $errors->has($name))
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
