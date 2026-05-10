<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'UaiLabs') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        body {
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #1e293b;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, .55);
        }

        /* ── Header ─────────────────────────────────────────────────────────── */
        .card-head {
            padding: 1.5rem 1.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .brand-name {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .clock-wrap { text-align: right; }
        .clock-time { font-size: 1.65rem; font-weight: 700; color: #fff; letter-spacing: -.5px; line-height: 1; }
        .clock-date { font-size: .7rem; color: rgba(255,255,255,.35); margin-top: 3px; }

        /* ── Tabs ────────────────────────────────────────────────────────────── */
        .tab-nav {
            display: flex;
            border-bottom: 1px solid rgba(255,255,255,.08);
            padding: 0 1.75rem;
        }

        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            color: rgba(255,255,255,.35);
            font-size: .85rem;
            font-weight: 600;
            padding: .8rem 0;
            cursor: pointer;
            transition: color .2s, border-color .2s;
            letter-spacing: .02em;
        }

        .tab-btn.active {
            color: #60a5fa;
            border-bottom-color: #60a5fa;
        }

        /* ── Tab content ─────────────────────────────────────────────────────── */
        .tab-content { padding: 1.75rem; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── Form fields ─────────────────────────────────────────────────────── */
        .field-label {
            display: block;
            font-size: .72rem;
            font-weight: 600;
            color: rgba(255,255,255,.45);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: .4rem;
        }

        .field-input {
            width: 100%;
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 10px;
            color: #fff;
            font-size: .95rem;
            padding: .65rem .9rem;
            transition: border-color .2s;
        }

        .field-input:focus { outline: none; border-color: #60a5fa; }
        .field-input.is-invalid { border-color: #ef4444; }
        .field-input::placeholder { color: #475569; }

        .invalid-text { color: #f87171; font-size: .78rem; margin-top: .3rem; }

        /* ── Buttons ─────────────────────────────────────────────────────────── */
        .btn-grad {
            width: 100%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .8rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s;
        }

        .btn-grad:hover { opacity: .9; }
        .btn-grad:disabled { opacity: .4; cursor: not-allowed; }

        /* ── Login tab extras ────────────────────────────────────────────────── */
        .login-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .check-label {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .8rem;
            color: rgba(255,255,255,.45);
            cursor: pointer;
        }

        .link-muted { font-size: .78rem; color: rgba(255,255,255,.3); text-decoration: none; }
        .link-muted:hover { color: rgba(255,255,255,.55); }

        /* ── Feedback boxes ──────────────────────────────────────────────────── */
        .feedback {
            border-radius: 10px;
            padding: .7rem 1rem;
            font-size: .88rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .feedback-success { background: rgba(34,197,94,.13); color: #86efac; border: 1px solid rgba(34,197,94,.2); }
        .feedback-error   { background: rgba(239,68,68,.13);  color: #fca5a5; border: 1px solid rgba(239,68,68,.2); }

        /* ── Clock-in inline result ──────────────────────────────────────────── */
        .clockin-result {
            text-align: center;
            padding: 2rem 1rem;
            display: none;
        }

        .clockin-result .ci-icon { font-size: 3rem; color: #34d399; }
        .clockin-result .ci-msg  { color: #86efac; font-size: 1rem; margin: .6rem 0 0; }
        .clockin-result .ci-name { color: rgba(255,255,255,.4); font-size: .82rem; margin-top: .25rem; }

        /* ── Unit selector step ──────────────────────────────────────────────── */
        .unit-option {
            display: flex;
            align-items: center;
            gap: .75rem;
            background: #0f172a;
            border-radius: 10px;
            padding: .7rem 1rem;
            margin-bottom: .5rem;
            cursor: pointer;
            color: #fff;
            font-size: .9rem;
        }

        .back-link {
            background: none;
            border: none;
            color: rgba(255,255,255,.3);
            font-size: .8rem;
            width: 100%;
            margin-top: .75rem;
            cursor: pointer;
            text-align: center;
        }

        .back-link:hover { color: rgba(255,255,255,.55); }

        /* ── Spinner ─────────────────────────────────────────────────────────── */
        .spinner {
            display: inline-block;
            width: 1rem; height: 1rem;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            vertical-align: middle;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Clock-out overlay ───────────────────────────────────────────────── */
        .co-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.78);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 9999;
            animation: fadeIn .25s ease;
        }

        .co-overlay.show { display: flex; }

        .co-card {
            background: #1e293b;
            border-radius: 20px;
            padding: 2rem 1.75rem 1.5rem;
            max-width: 340px;
            width: 100%;
            text-align: center;
            position: relative;
            box-shadow: 0 30px 60px rgba(0,0,0,.6);
        }

        .co-close {
            position: absolute;
            top: 1rem; right: 1rem;
            background: none;
            border: none;
            color: rgba(255,255,255,.35);
            font-size: 1.2rem;
            cursor: pointer;
            line-height: 1;
        }

        .co-icon  { font-size: 3rem; color: #34d399; margin-bottom: .5rem; }
        .co-title { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: .25rem; }
        .co-name  { font-size: .82rem; color: rgba(255,255,255,.4); margin-bottom: 1.25rem; }

        .co-stat {
            background: #0f172a;
            border-radius: 10px;
            padding: .7rem 1rem;
            margin-bottom: .5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .co-stat-label { font-size: .72rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: .05em; }
        .co-stat-value { font-size: 1rem; font-weight: 700; color: #fff; }

        .co-timer { font-size: .72rem; color: rgba(255,255,255,.25); margin-top: 1rem; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>

{{-- ── Card principal ──────────────────────────────────────────────────── --}}
<div class="login-card">

    {{-- Header: marca + relógio do dispositivo --}}
    <div class="card-head">
        <div class="brand-name">UaiLabs</div>
        <div class="clock-wrap">
            <div class="clock-time" id="clockTime">--:--</div>
            <div class="clock-date" id="clockDate"></div>
        </div>
    </div>

    {{-- Abas --}}
    <div class="tab-nav">
        <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">
            <i class="bi bi-person me-1"></i>Login
        </button>
        <button class="tab-btn" id="tab-ponto" onclick="switchTab('ponto')">
            <i class="bi bi-clock-history me-1"></i>Ponto
        </button>
    </div>

    {{-- Conteúdo das abas --}}
    <div class="tab-content">

        {{-- ── ABA LOGIN ──────────────────────────────────────────────────── --}}
        <div class="tab-pane active" id="pane-login">

            @if(session('status'))
                <div class="feedback feedback-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label class="field-label">Usuário / E-mail</label>
                    <input type="text" name="login" value="{{ old('login') }}"
                           class="field-input @error('login') is-invalid @enderror"
                           placeholder="usuário ou e-mail"
                           required autofocus autocomplete="username">
                    @error('login')
                        <div class="invalid-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="field-label">Senha</label>
                    <input type="password" name="password"
                           class="field-input @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    @error('password')
                        <div class="invalid-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="login-row">
                    <label class="check-label">
                        <input type="checkbox" name="remember" style="accent-color:#60a5fa">
                        Lembrar-me
                    </label>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="link-muted">Esqueci a senha</a>
                    @endif
                </div>

                <button type="submit" class="btn-grad">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                </button>
            </form>
        </div>

        {{-- ── ABA PONTO ───────────────────────────────────────────────────── --}}
        <div class="tab-pane" id="pane-ponto">

            {{-- Feedback de erro --}}
            <div id="pontoFeedback"></div>

            {{-- Etapa 1: formulário de credenciais --}}
            <div id="stepCred">
                <div class="mb-3">
                    <label class="field-label">Usuário</label>
                    <input type="text" id="pontoUser"
                           class="field-input"
                           placeholder="seu usuário"
                           autocomplete="username" autocapitalize="off" spellcheck="false">
                </div>

                <div class="mb-4">
                    <label class="field-label">Senha</label>
                    <input type="password" id="pontoPass"
                           class="field-input"
                           autocomplete="current-password">
                </div>

                <button type="button" class="btn-grad" id="pontoBtn" onclick="submitPonto()">
                    <i class="bi bi-clock-history me-2"></i>Registrar Ponto
                </button>
            </div>

            {{-- Etapa 2: seleção de unidade (múltiplas unidades) --}}
            <div id="stepUnit" style="display:none">
                <p style="color:rgba(255,255,255,.55);font-size:.88rem;margin-bottom:1rem">
                    Olá, <strong id="unitUserName" style="color:#fff"></strong>. Selecione a unidade:
                </p>
                <div id="unitList" class="mb-4"></div>
                <button type="button" class="btn-grad" onclick="confirmUnit()">Confirmar</button>
                <button type="button" class="back-link" onclick="backToCred()">← Voltar</button>
            </div>

            {{-- Etapa 3: resultado de clock_in (inline) --}}
            <div class="clockin-result" id="clockinResult">
                <div class="ci-icon"><i class="bi bi-check-circle-fill"></i></div>
                <p class="ci-msg" id="clockinMsg"></p>
                <p class="ci-name" id="clockinName"></p>
            </div>

        </div>
    </div>
</div>

{{-- ── Overlay de clock_out ────────────────────────────────────────────── --}}
<div class="co-overlay" id="coOverlay">
    <div class="co-card">
        <button class="co-close" onclick="closeOverlay()"><i class="bi bi-x-lg"></i></button>
        <div class="co-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="co-title">Saída registrada!</div>
        <div class="co-name" id="coName"></div>

        <div class="co-stat">
            <span class="co-stat-label">Entrada</span>
            <span class="co-stat-value" id="coEntrada">--:--</span>
        </div>
        <div class="co-stat">
            <span class="co-stat-label">Saída</span>
            <span class="co-stat-value" id="coSaida">--:--</span>
        </div>
        <div class="co-stat">
            <span class="co-stat-label">Trabalhado hoje</span>
            <span class="co-stat-value" id="coWorked">--</span>
        </div>

        <div class="co-timer" id="coTimer">Fechando em 10s</div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ── Relógio do dispositivo ────────────────────────────────────────────
    function tick() {
        var now = new Date();
        document.getElementById('clockTime').textContent =
            now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        document.getElementById('clockDate').textContent =
            now.toLocaleDateString('pt-BR', { weekday: 'short', day: 'numeric', month: 'short' });
    }
    tick();
    setInterval(tick, 1000);

    // ── Troca de abas ────────────────────────────────────────────────────
    window.switchTab = function (tab) {
        ['login', 'ponto'].forEach(function (t) {
            document.getElementById('pane-' + t).classList.remove('active');
            document.getElementById('tab-' + t).classList.remove('active');
        });
        document.getElementById('pane-' + tab).classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
        if (tab === 'ponto') document.getElementById('pontoUser').focus();
    };

    // ── Estado interno do Ponto ───────────────────────────────────────────
    var _user = '';
    var _pass = '';
    var _coTimer;

    // ── Submit de credenciais ─────────────────────────────────────────────
    window.submitPonto = function () {
        _user = document.getElementById('pontoUser').value.trim();
        _pass = document.getElementById('pontoPass').value;

        if (!_user || !_pass) {
            showFeedback('error', 'Preencha usuário e senha.');
            return;
        }

        setLoading(true);
        clearFeedback();
        punch({ username: _user, password: _pass });
    };

    // ── Confirmar unidade (etapa 2) ───────────────────────────────────────
    window.confirmUnit = function () {
        var sel = document.querySelector('input[name="unitRadio"]:checked');
        if (!sel) { showFeedback('error', 'Selecione uma unidade.'); return; }
        setLoading(true);
        clearFeedback();
        punch({ username: _user, password: _pass, unit_id: sel.value });
    };

    // ── Fetch de punch ────────────────────────────────────────────────────
    function punch(payload) {
        fetch('{{ route('clock.credential') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(function (r) {
            return r.json().then(function (d) { return { ok: r.ok, data: d }; });
        })
        .then(function (res) {
            setLoading(false);

            if (!res.ok) {
                backToCred();
                showFeedback('error', res.data.error || 'Erro ao registrar ponto.');
                return;
            }

            if (res.data.needs_unit) {
                showUnitStep(res.data.user_name, res.data.units);
                return;
            }

            handleSuccess(res.data);
        })
        .catch(function () {
            setLoading(false);
            backToCred();
            showFeedback('error', 'Erro de conexão. Tente novamente.');
        });
    }

    // ── Resultado de sucesso ──────────────────────────────────────────────
    function handleSuccess(data) {
        resetForm();

        if (data.type === 'clock_in') {
            document.getElementById('stepCred').style.display = 'none';
            document.getElementById('clockinMsg').textContent  = data.message;
            document.getElementById('clockinName').textContent = data.name;
            document.getElementById('clockinResult').style.display = 'block';

            setTimeout(function () {
                document.getElementById('clockinResult').style.display = 'none';
                document.getElementById('stepCred').style.display = 'block';
                document.getElementById('pontoUser').focus();
            }, 8000);
        } else {
            document.getElementById('coName').textContent    = data.name;
            document.getElementById('coEntrada').textContent = data.clock_in_time || '--:--';
            document.getElementById('coSaida').textContent   = data.time;
            document.getElementById('coWorked').textContent  = data.worked;
            document.getElementById('coOverlay').classList.add('show');
            startCoTimer();
        }
    }

    // ── Overlay de clock_out ──────────────────────────────────────────────
    function startCoTimer() {
        var secs = 10;
        var el = document.getElementById('coTimer');
        el.textContent = 'Fechando em ' + secs + 's';
        _coTimer = setInterval(function () {
            secs--;
            if (secs <= 0) { closeOverlay(); return; }
            el.textContent = 'Fechando em ' + secs + 's';
        }, 1000);
    }

    window.closeOverlay = function () {
        clearInterval(_coTimer);
        document.getElementById('coOverlay').classList.remove('show');
        document.getElementById('pontoUser').focus();
    };

    // ── Etapa de seleção de unidade ───────────────────────────────────────
    function showUnitStep(name, units) {
        document.getElementById('unitUserName').textContent = name;
        var list = document.getElementById('unitList');
        list.innerHTML = '';
        units.forEach(function (u) {
            var label = document.createElement('label');
            label.className = 'unit-option';
            label.innerHTML =
                '<input type="radio" name="unitRadio" value="' + u.id + '" style="accent-color:#60a5fa"> ' +
                u.name;
            list.appendChild(label);
        });
        document.getElementById('stepCred').style.display = 'none';
        document.getElementById('stepUnit').style.display = 'block';
    }

    window.backToCred = function () {
        document.getElementById('stepUnit').style.display = 'none';
        document.getElementById('stepCred').style.display = 'block';
        setLoading(false);
        document.getElementById('pontoPass').value = '';
        document.getElementById('pontoPass').focus();
    };

    // ── Helpers ───────────────────────────────────────────────────────────
    function setLoading(on) {
        var btn = document.getElementById('pontoBtn');
        if (!btn) return;
        btn.disabled = on;
        btn.innerHTML = on
            ? '<span class="spinner"></span>'
            : '<i class="bi bi-clock-history me-2"></i>Registrar Ponto';
    }

    function showFeedback(type, msg) {
        var cls = type === 'success' ? 'feedback-success' : 'feedback-error';
        document.getElementById('pontoFeedback').innerHTML =
            '<div class="feedback ' + cls + '">' + msg + '</div>';
    }

    function clearFeedback() {
        document.getElementById('pontoFeedback').innerHTML = '';
    }

    function resetForm() {
        document.getElementById('pontoUser').value = '';
        document.getElementById('pontoPass').value = '';
        _user = '';
        _pass = '';
    }

    // Enter key no campo de senha do Ponto
    document.getElementById('pontoPass').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); submitPonto(); }
    });
})();
</script>
</body>
</html>
