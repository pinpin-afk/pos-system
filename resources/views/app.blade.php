<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'POS') }}</title>
        <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
        <link rel="shortcut icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.jpg') }}">
        <script>
            try {
                if (localStorage.getItem('pos-theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            } catch (error) {}
        </script>
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-[var(--admin-canvas)] font-sans text-slate-900 antialiased">
        @inertia
    </body>
</html>
