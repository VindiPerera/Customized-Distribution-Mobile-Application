<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-critical border border-transparent rounded-lg font-medium text-sm text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-critical focus:ring-offset-2 focus:ring-offset-surface active:scale-[0.98] transition']) }}>
    {{ $slot }}
</button>
