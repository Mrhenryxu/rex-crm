<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        {{-- Apply the saved colour scheme before React renders to avoid a light/dark flash. --}}
        <script>
            try {
                var stored = window.localStorage.getItem('rex-crm-color-scheme');
                var scheme = stored === 'light' || stored === 'dark'
                    ? stored
                    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-mantine-color-scheme', scheme);
            } catch (error) {}
        </script>
        @viteReactRefresh
        @vite('resources/js/main.tsx')
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
