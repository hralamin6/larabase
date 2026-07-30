@props([
    'sidebar' => false,
])

<a {{ $attributes->merge(['href' => '#', 'class' => 'flex items-center gap-2']) }}>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary">
        <x-app-logo-icon class="size-5 fill-current text-primary-content" />
    </div>
    <span class="font-semibold text-base text-base-content">{{ config('app.name', 'Laravel') }}</span>
</a>
