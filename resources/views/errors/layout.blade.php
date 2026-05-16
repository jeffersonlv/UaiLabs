<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') — UaiLabs</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .error-code { font-size: 5rem; font-weight: 700; color: #dee2e6; line-height: 1; }
    </style>
</head>
<body>
    <div class="text-center px-3">
        <div class="error-code">@yield('code')</div>
        <h4 class="mt-2 mb-1 fw-semibold">@yield('title')</h4>
        <p class="text-muted mb-4" style="max-width:380px;margin:0 auto">@yield('message')</p>
        <div class="d-flex gap-2 justify-content-center">
            <button onclick="history.back()" class="btn btn-outline-secondary">← Voltar</button>
            @auth
            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
            @endauth
        </div>
    </div>
</body>
</html>
