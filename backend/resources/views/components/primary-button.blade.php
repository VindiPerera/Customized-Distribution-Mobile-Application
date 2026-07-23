<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-accent border border-transparent rounded-lg font-medium text-sm text-white hover:bg-accent-hover focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-surface active:scale-[0.98] transition']) }}>
    {{ $slot }}
</button>
