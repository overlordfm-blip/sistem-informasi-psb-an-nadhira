@props(['name', 'label', 'value' => null, 'required' => false, 'rows' => 4])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-1 block text-base font-semibold">{{ $label }}</label>
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @required($required)
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->except('class')->class('w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks placeholder:text-teks/60 focus:border-primer focus:outline-2 focus:outline-primer') }}
    >{{ old($name, $value) }}</textarea>
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-base text-bahaya">{{ $message }}</p>
    @enderror
</div>