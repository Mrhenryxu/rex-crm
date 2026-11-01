<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        {{-- Rex favicons, self-hosted copies of the rexsoftware.com assets. The browser picks one by the OS colour scheme. --}}
        <link href="{{ asset('favicon-light.png') }}" rel="icon" type="image/png" sizes="32x32" media="(prefers-color-scheme: light)">
        <link href="{{ asset('favicon-dark.png') }}" rel="icon" type="image/png" sizes="32x32" media="(prefers-color-scheme: dark)">
        <link href="{{ asset('apple-touch-icon.png') }}" rel="apple-touch-icon">
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
