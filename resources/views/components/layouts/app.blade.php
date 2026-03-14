<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main class="min-h-screen bg-rose-50/25 dark:bg-zinc-800">
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
