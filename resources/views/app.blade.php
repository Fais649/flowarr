<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cg fill='%237C3AED'%3E%3Cellipse cx='24' cy='10' rx='6' ry='10' transform='rotate(0 24 24)' opacity='0.9'/%3E%3Cellipse cx='24' cy='10' rx='6' ry='10' transform='rotate(72 24 24)' opacity='0.85'/%3E%3Cellipse cx='24' cy='10' rx='6' ry='10' transform='rotate(144 24 24)' opacity='0.9'/%3E%3Cellipse cx='24' cy='10' rx='6' ry='10' transform='rotate(216 24 24)' opacity='0.85'/%3E%3Cellipse cx='24' cy='10' rx='6' ry='10' transform='rotate(288 24 24)' opacity='0.9'/%3E%3Ccircle cx='24' cy='24' r='4' opacity='0.95'/%3E%3C/g%3E%3C/svg%3E" sizes="any">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Flowarr') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
