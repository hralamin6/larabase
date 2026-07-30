@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-2xl font-bold text-base-content">{{ $title }}</h1>
    <p class="mt-1 text-sm text-base-content/60">{{ $description }}</p>
</div>

