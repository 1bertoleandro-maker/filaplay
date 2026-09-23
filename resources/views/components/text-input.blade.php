@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'min-h-14 w-full rounded-2xl border-line bg-canvas text-lg text-white shadow-sm focus:border-brand focus:ring-brand']) }}>
