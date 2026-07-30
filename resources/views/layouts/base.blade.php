@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>
            {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
        </title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- DaisyUI theme init: restore from localStorage on initial load & Livewire SPA navigation --}}
        <script>
            function applyTheme() {
                try {
                    var raw = localStorage.getItem('mary-theme') || '"light"';
                    var theme = 'light';
                    if (raw.startsWith('"')) {
                        theme = JSON.parse(raw);
                    } else {
                        theme = raw;
                        localStorage.setItem('mary-theme', JSON.stringify(raw));
                    }
                    document.documentElement.setAttribute('data-theme', theme);
                    var darkThemes = ['dark', 'synthwave', 'halloween', 'forest', 'black', 'luxury', 'dracula', 'business', 'night', 'coffee', 'dim', 'sunset', 'abyss'];
                    var themeClass = darkThemes.includes(theme) ? 'dark' : 'light';
                    document.documentElement.setAttribute('class', themeClass);
                    localStorage.setItem('mary-class', JSON.stringify(themeClass));
                } catch (e) {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            }
            applyTheme();
            document.addEventListener('livewire:navigated', applyTheme);
        </script>


    </head>
    <body  {{ $attributes->merge(['class' => 'min-h-screen bg-base-200 antialiased']) }}>
        <div>
            {{ $slot }}

            {{-- Toast notifications --}}
            <x-toast />
        </div>
    </body>


</html>
