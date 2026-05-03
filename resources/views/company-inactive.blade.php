<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conta Inativa — UaiLabs</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body style="margin:0; background:#f1f5f9; min-height:100vh; display:flex; align-items:center; justify-content:center;">
    <div style="
        width:100%;
        max-width:420px;
        background:#fff;
        border:2px solid #fca5a5;
        border-radius:16px;
        box-shadow:0 4px 24px rgba(0,0,0,0.08);
        padding:2.5rem 2.5rem 2rem;
        text-align:center;
    ">
        <div style="font-size:3rem; margin-bottom:1rem;">⚠️</div>

        <div style="
            font-family:'Times New Roman', Times, serif;
            font-size:1.5rem;
            font-weight:bold;
            color:#1e293b;
            margin-bottom:0.5rem;
        ">
            UaiLabs
        </div>

        <hr style="border-color:#e2e8f0; margin:1rem 0;">

        <h5 style="color:#dc2626; font-weight:600; margin-bottom:0.75rem;">Empresa Inativa</h5>

        <p style="color:#64748b; font-size:0.9rem; margin-bottom:2rem; line-height:1.6;">
            A empresa vinculada à sua conta está <strong>desativada</strong>.<br>
            Entre em contato com o administrador do sistema para reativar o acesso.
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="
                width:100%;
                padding:0.6rem 1.5rem;
                background:#dc2626;
                color:#fff;
                font-size:0.9rem;
                font-weight:600;
                border:none;
                border-radius:8px;
                cursor:pointer;
            " onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                Sair do sistema
            </button>
        </form>
    </div>
</body>
</html>
