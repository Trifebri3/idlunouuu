<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $profile = $page['props']['profile'] ?? null;
            $appName = (config('app.name') && config('app.name') !== 'Laravel') ? config('app.name') : 'LUNOU';
            $pageTitle = $profile
                ? "{$profile['display_name']} (@{$profile['username']}) · {$appName}"
                : "{$appName} · Social Identity & NFC Card";
            $pageDescription = $profile
                ? ($profile['bio'] ?: "Profil identitas digital resmi {$profile['display_name']} di {$appName}. Terhubung via kartu fisik pintar YOIMO NFC.")
                : 'Platform identitas digital personal dan kartu fisik pintar YOIMO NFC.';
            $pageImage = ($profile && !empty($profile['avatar']))
                ? $profile['avatar']
                : asset('icon/1.png');
            $pageUrl = $profile
                ? url('/' . $profile['username'])
                : url()->current();
        @endphp

        <title inertia>{{ $pageTitle }}</title>

        <!-- Dynamic SEO / Open Graph & NFC Tag Scanner Preview -->
        <meta name="description" content="{{ $pageDescription }}">
        <meta property="og:site_name" content="{{ $appName }}">
        <meta property="og:type" content="{{ $profile ? 'profile' : 'website' }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:image" content="{{ $pageImage }}">
        <meta property="og:url" content="{{ $pageUrl }}">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">
        <meta name="twitter:image" content="{{ $pageImage }}">

        <!-- Mobile App Icons & Native NFC Tap Banner Support -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icon/1.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icon/1.png') }}">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="apple-mobile-web-app-title" content="{{ $appName }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="theme-color" content="#ffffff">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-[#fbfbfd] text-zinc-900">
        @inertia
    </body>
</html>
