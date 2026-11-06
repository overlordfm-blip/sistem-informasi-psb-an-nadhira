@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-1 block text-base font-semibold">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @required($required)
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->except('class')->class('min-h-11 w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks placeholder:text-teks/60 focus:border-primer focus:outline-2 focus:outline-primer') }}
    >
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-base text-bahaya">{{ $message }}</p>
    @enderror
</div>