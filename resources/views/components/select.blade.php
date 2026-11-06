@props(['name', 'label', 'options' => [], 'selected' => null, 'required' => false])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-1 block text-base font-semibold">{{ $label }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @required($required)
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->except('class')->class('min-h-11 w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks focus:border-primer focus:outline-2 focus:outline-primer') }}
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $selected) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-base text-bahaya">{{ $message }}</p>
    @enderror
</div>