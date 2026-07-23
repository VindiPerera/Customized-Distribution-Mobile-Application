@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[0.8rem] font-medium text-ink-soft mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
