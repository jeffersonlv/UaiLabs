<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'UaiLabs') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body style="margin:0; background:#f1f5f9; min-height:100vh; display:flex; align-items:center; justify-content:center;">
        <div style="
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 2px solid #cbd5e1;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            padding: 2.5rem 2.5rem 2rem;
        ">
            <div style="
                font-family: 'Times New Roman', Times, serif;
                font-size: 1.9rem;
                font-weight: bold;
                text-align: center;
                color: #1e293b;
                letter-spacing: 1px;
                margin-bottom: 1.75rem;
                padding-bottom: 1.25rem;
                border-bottom: 1px solid #e2e8f0;
            ">
                UaiLabs
            </div>

            {{ $slot }}
        </div>
    </body>
</html>
