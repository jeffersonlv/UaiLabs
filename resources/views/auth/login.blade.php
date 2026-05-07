<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'UaiLabs') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .login-wrapper { width: 100%; max-width: 860px; }
        .panel-clock { background: #1e293b; border-radius: 16px 0 0 16px; padding: 2.5rem; color: #fff; }
        .panel-login { background: #fff; border-radius: 0 16px 16px 0; padding: 2.5rem; }
        @media (max-width: 767px) {
            .panel-clock  { border-radius: 16px 16px 0 0; }
            .panel-login  { border-radius: 0 0 16px 16px; }
        }
        .brand-title { font-size: 1.6rem; font-weight: 800; letter-spacing: -.5px; }
        .brand-title span { background: linear-gradient(135deg, #60a5fa, #a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .clock-time { font-size: 3rem; font-weight: 700; letter-spacing: -1px; line-height: 1; }
        .clock-date { font-size: .85rem; color: rgba(255,255,255,.5); }
        .pin-input {
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 10px;
            color: #fff;
            font-size: 2rem;
            letter-spacing: .4em;
            text-align: center;
            padding: .5rem 1rem;
            width: 100%;
            transition: border-color .2s;
        }
        .pin-input:focus { outline: none; border-color: #60a5fa; }
        .pin-input::placeholder { font-size: 1rem; letter-spacing: normal; color: #475569; }
        .form-control-dark {
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 10px;
            color: #fff;
            padding: .55rem .9rem;
            width: 100%;
            font-size: .9rem;
            transition: border-color .2s;
        }
        .form-control-dark:focus { outline: none; border-color: #60a5fa; background: #0f172a; color: #fff; }
        .form-control-dark::placeholder { color: #475569; }
        .form-control-dark option { background: #1e293b; color: #fff; }
        .btn-clock {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .75rem;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: opacity .2s;
        }
        .btn-clock:hover { opacity: .9; }
        .btn-clock:disabled { opacity: .4; cursor: not-allowed; }
        .label-dark { font-size: .78rem; font-weight: 600; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .05em; margin-bottom: .35rem; }
        .clock-feedback { border-radius: 10px; padding: .7rem 1rem; font-size: .9rem; text-align: center; }
        .unit-skeleton { height: 46px; background: #1a2744; border-radius: 10px; animation: pulse .8s ease-in-out infinite alternate; }
        @keyframes pulse { from { opacity: .4; } to { opacity: .8; } }
        .divider-or { display: flex; align-items: center; gap: .75rem; color: #94a3b8; font-size: .8rem; margin: 1.25rem 0; }
        .divider-or::before, .divider-or::after { content: ''; flex: 1; height: 1px; background: #334155; }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="d-flex flex-column flex-md-row shadow-lg" style="border-radius:16px;overflow:hidden">

        {{-- ── Painel Bater Ponto ─────────────────────────── --}}
        <div class="panel-clock flex-md-fill" style="flex-basis:55%">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <div class="brand-title mb-1"><span>UaiLabs</span></div>
                    <div class="clock-date" id="clockDate"></div>
                </div>
                <div class="text-end">
                    <div class="clock-time" id="clockTime">--:--</div>
                </div>
            </div>

            {{-- Feedback --}}
            @if(session('clock_message'))
                <div class="clock-feedback mb-3
                    {{ session('clock_type') === 'clock_in' ? 'bg-success bg-opacity-25 text-success' : 'bg-info bg-opacity-25 text-info' }}">
                    <i class="bi bi-check-circle me-2"></i>{{ session('clock_message') }}
                </div>
            @endif
            @if($errors->has('pin') || $errors->has('username'))
                <div class="clock-feedback mb-3 bg-danger bg-opacity-25 text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ $errors->first('pin') ?: $errors->first('username') }}
                </div>
            @endif

            <form method="POST" action="{{ route('clock.punch.guest') }}" id="clockForm">
                @csrf

                {{-- Username --}}
                <div class="mb-3">
                    <div class="label-dark">Usuário</div>
                    <input id="clockUsername" name="username" type="text"
                           class="form-control-dark"
                           placeholder="Digite seu usuário"
                           value="{{ old('username') }}"
                           autocomplete="off" autocapitalize="off" spellcheck="false"
                           required>
                </div>

                {{-- Unidade --}}
                <div class="mb-3" id="unitWrapper">
                    <div class="label-dark">Unidade</div>
                    <div id="unitSkeleton" class="unit-skeleton d-none"></div>
                    <select id="clockUnit" name="unit_id" class="form-control-dark d-none" required>
                        <option value="">Selecione a unidade</option>
                    </select>
                    <div id="unitEmpty" class="form-control-dark text-muted d-none" style="cursor:default">
                        — sem unidades vinculadas —
                    </div>
                    <div id="unitHint" class="form-control-dark" style="cursor:default;color:#475569">
                        Digite o usuário acima
                    </div>
                </div>

                {{-- PIN --}}
                <div class="mb-4">
                    <div class="label-dark">PIN</div>
                    <input id="clockPin" name="pin" type="password"
                           class="pin-input"
                           placeholder="••••"
                           maxlength="6" inputmode="numeric"
                           autocomplete="current-password"
                           required>
                </div>

                <button type="submit" class="btn-clock" id="clockBtn">
                    <i class="bi bi-fingerprint me-2"></i>Registrar Ponto
                </button>
            </form>
        </div>

        {{-- ── Painel Login ───────────────────────────────── --}}
        <div class="panel-login d-flex flex-column justify-content-center" style="flex-basis:45%;min-width:280px">
            <div class="text-center mb-4">
                <div class="fw-bold text-dark" style="font-size:1.1rem">Acesso ao Sistema</div>
                <div class="text-muted" style="font-size:.8rem">Para gestores e administradores</div>
            </div>

            @if(session('status'))
                <div class="alert alert-success py-2 mb-3" style="font-size:.85rem">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.85rem">Usuário / E-mail</label>
                    <input id="login" type="text" name="login"
                           value="{{ old('login') }}"
                           class="form-control @error('login') is-invalid @enderror"
                           placeholder="usuário ou e-mail"
                           required autofocus autocomplete="username">
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.85rem">Senha</label>
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember" style="font-size:.83rem">Lembrar-me</label>
                    </div>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" style="font-size:.78rem;color:#64748b">
                            Esqueci a senha
                        </a>
                    @endif
                </div>
                <button type="submit" class="btn btn-dark w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                </button>
            </form>
        </div>

    </div>
</div>

<script>
(function () {
    // Live clock
    function tick() {
        var now = new Date();
        document.getElementById('clockTime').textContent =
            now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        document.getElementById('clockDate').textContent =
            now.toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' });
    }
    tick();
    setInterval(tick, 1000);

    // Unit auto-load on username change
    var usernameEl = document.getElementById('clockUsername');
    var unitSelect = document.getElementById('clockUnit');
    var unitSkeleton = document.getElementById('unitSkeleton');
    var unitEmpty   = document.getElementById('unitEmpty');
    var unitHint    = document.getElementById('unitHint');
    var loadTimer;

    function showState(state) {
        unitSelect.classList.add('d-none');
        unitSkeleton.classList.add('d-none');
        unitEmpty.classList.add('d-none');
        unitHint.classList.add('d-none');
        if (state === 'select')   unitSelect.classList.remove('d-none');
        if (state === 'loading')  unitSkeleton.classList.remove('d-none');
        if (state === 'empty')    unitEmpty.classList.remove('d-none');
        if (state === 'hint')     unitHint.classList.remove('d-none');
    }

    function loadUnits(username) {
        if (!username.trim()) { showState('hint'); return; }
        showState('loading');
        fetch('{{ route('clock.units') }}?username=' + encodeURIComponent(username))
            .then(function(r) { return r.json(); })
            .then(function(units) {
                unitSelect.innerHTML = '';
                if (!units.length) { showState('empty'); return; }
                if (units.length === 1) {
                    // single unit: hidden select, auto-select
                    var opt = document.createElement('option');
                    opt.value = units[0].id;
                    opt.textContent = units[0].name;
                    opt.selected = true;
                    unitSelect.appendChild(opt);
                    showState('select');
                } else {
                    var placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Selecione a unidade';
                    unitSelect.appendChild(placeholder);
                    units.forEach(function(u) {
                        var opt = document.createElement('option');
                        opt.value = u.id;
                        opt.textContent = u.name;
                        unitSelect.appendChild(opt);
                    });
                    showState('select');
                }
            })
            .catch(function() { showState('hint'); });
    }

    usernameEl.addEventListener('input', function () {
        clearTimeout(loadTimer);
        loadTimer = setTimeout(function () { loadUnits(usernameEl.value); }, 400);
    });

    // If username was pre-filled (old value after error), load immediately
    if (usernameEl.value) { loadUnits(usernameEl.value); }

    // Auto-focus PIN when unit is selected
    unitSelect.addEventListener('change', function () {
        if (unitSelect.value) document.getElementById('clockPin').focus();
    });
})();
</script>
</body>
</html>