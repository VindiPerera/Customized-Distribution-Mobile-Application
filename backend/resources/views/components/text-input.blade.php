@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line bg-surface text-ink placeholder:text-ink-soft/60 rounded-lg shadow-sm text-sm focus:border-accent focus:ring-accent focus:ring-1 transition-colors']) }}>
