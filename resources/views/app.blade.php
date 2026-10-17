<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Peminjaman NFC') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('logo-nfc.png') }}" />
        <link rel="apple-touch-icon" href="{{ asset('logo-nfc.png') }}" />

        <!-- Fonts: Google Sans + Roboto Slab (dimuat async agar tidak memblokir render) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://fonts.cdnfonts.com" crossorigin>
        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&family=Roboto+Slab:wght@700;800&display=swap"
            media="print"
            onload="this.media='all'"
        >
        <link
            rel="stylesheet"
            href="https://fonts.cdnfonts.com/css/google-sans"
            media="print"
            onload="this.media='all'"
        >
        <noscript>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&family=Roboto+Slab:wght@700;800&display=swap">
            <link rel="stylesheet" href="https://fonts.cdnfonts.com/css/google-sans">
        </noscript>
        <style>
            /* Google Sans priority with DM Sans fallback */
            body { font-family: 'Google Sans', 'DM Sans', ui-sans-serif, system-ui, -apple-system, sans-serif; }
        </style>

        <!-- Flowbite Vue (di-copied oleh vite.config.js, di luar pipeline PostCSS) -->
        <link rel="stylesheet" href="{{ asset('vendor/flowbite-vue.css') }}" />

        <!-- Scripts -->
        @routes
        <script>
            if (typeof Ziggy !== 'undefined') {
                window.Ziggy = Ziggy;
            }
        </script>
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
