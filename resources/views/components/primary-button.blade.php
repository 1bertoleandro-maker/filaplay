<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-14 items-center justify-center rounded-2xl bg-brand px-6 text-base font-bold text-canvas transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 focus:ring-offset-card']) }}>
    {{ $slot }}
</button>
