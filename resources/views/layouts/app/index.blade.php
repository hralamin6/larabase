<x-layouts::app.sidebar :title="$title ?? null">
    <main class="p-6">
        {{ $slot }}
    </main>
</x-layouts::app.sidebar>
