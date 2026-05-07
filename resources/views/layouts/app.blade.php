<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @php
            $titleUser  = auth()->user();
            $titleParts = ['UaiLabs'];
            if ($titleUser?->company) $titleParts[] = $titleUser->company->name;
            if ($titleUser && !$titleUser->isAdminOrAbove()) {
                $units = $titleUser->units;
                if ($units->count() === 1) $titleParts[] = $units->first()->name;
            }
        @endphp
        {{ implode(' — ', $titleParts) }}
        @hasSection('title') | @yield('title') @endif
    </title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Bootstrap JS (synchronous) — needed for alert dismiss, modals elsewhere in app --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        /* ══════════════════════════════════════════
           TOPBAR
        ══════════════════════════════════════════ */
        * { box-sizing: border-box; }

        .topbar {
            background: #1a1a2e;
            display: flex;
            align-items: center;
            padding: 0 1rem;
            height: 54px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,.35);
        }

        /* Brand */
        .brand {
            display: flex; align-items: center; gap: .5rem;
            text-decoration: none; flex-shrink: 0;
        }
        .brand-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
        }
        .brand-name { font-weight: 700; color: #fff; font-size: .95rem; display: block; line-height: 1.1; }
        .brand-sub  { font-size: .63rem; color: rgba(255,255,255,.4); display: block; }

        /* Hamburger (mobile only) */
        .hamburger {
            display: none;
            margin-left: auto;
            background: none; border: none; cursor: pointer;
            color: rgba(255,255,255,.8);
            font-size: 1.35rem;
            padding: .3rem .4rem;
            border-radius: 6px;
            transition: background .15s;
        }
        .hamburger:hover { background: rgba(255,255,255,.08); }

        /* ── DESKTOP NAV ── */
        .nav-desktop {
            display: flex;
            align-items: center;
            flex: 1;
            gap: .1rem;
            margin: 0 .5rem;
        }
        .nav-right-desktop {
            display: flex;
            align-items: center;
            gap: .2rem;
            margin-left: auto;
        }

        /* ── MOBILE DRAWER ── */
        .nav-mobile {
            display: none;
            position: fixed;
            top: 54px; left: 0; right: 0; bottom: 0;
            background: #1a1a2e;
            overflow-y: auto;
            padding: .75rem 0 2rem;
            z-index: 999;
            transform: translateX(-100%);
            transition: transform .22s ease;
        }
        .nav-mobile.open { transform: translateX(0); }

        /* ══════════════════════════════════════════
           NAV LINKS (shared)
        ══════════════════════════════════════════ */
        .nav-link {
            display: flex; align-items: center; gap: .4rem;
            padding: .42rem .75rem;
            color: rgba(255,255,255,.75);
            text-decoration: none;
            border-radius: 6px;
            font-size: .875rem;
            border: none; background: none; cursor: pointer;
            white-space: nowrap;
            transition: background .15s, color .15s;
            font-family: inherit;
        }
        .nav-link:hover, .nav-link.open { background: rgba(255,255,255,.09); color: #fff; }
        .nav-link.active { color: #fff; background: rgba(255,255,255,.07); }
        .nav-link i { font-size: .88rem; }
        .caret { font-size: .65rem !important; margin-left: .1rem; transition: transform .2s; }
        .nav-link.open .caret { transform: rotate(180deg); }

        .nav-sep { width: 1px; height: 22px; background: rgba(255,255,255,.12); margin: 0 .3rem; flex-shrink: 0; }

        /* ══════════════════════════════════════════
           DESKTOP DROPDOWNS
        ══════════════════════════════════════════ */
        .dd-wrap { position: relative; }

        .dd-menu {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0,0,0,.18);
            min-width: 220px;
            padding: .4rem 0;
            opacity: 0; transform: translateY(-6px); pointer-events: none;
            transition: opacity .15s, transform .15s;
            z-index: 2000;
        }
        .dd-menu.dd-right { left: auto; right: 0; }
        .dd-menu.open { opacity: 1; transform: translateY(0); pointer-events: auto; }

        /* ══════════════════════════════════════════
           DROPDOWN ITEMS
        ══════════════════════════════════════════ */
        .dd-header {
            font-size: .67rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: #94a3b8;
            padding: .5rem 1rem .2rem;
        }
        .dd-item {
            display: flex; align-items: center; gap: .55rem;
            padding: .46rem 1rem;
            font-size: .875rem; color: #1e293b;
            text-decoration: none; cursor: pointer;
            transition: background .1s;
            border: none; background: none; width: 100%;
            font-family: inherit; text-align: left;
        }
        .dd-item:hover { background: #f1f5f9; }
        .dd-item.active { background: #eff6ff; color: #2563eb; font-weight: 500; }
        .dd-item i { width: 1rem; text-align: center; color: #64748b; font-size: .88rem; }
        .dd-item.active i { color: #2563eb; }
        .dd-item.disabled { color: #94a3b8; cursor: default; pointer-events: none; }
        .dd-item.disabled:hover { background: none; }
        .dd-item.danger { color: #dc2626; }
        .dd-item.danger i { color: #dc2626; }
        .dd-item.danger:hover { background: #fef2f2; }
        .dd-sep { height: 1px; background: #e2e8f0; margin: .3rem 0; }

        /* User dropdown header */
        .dd-user-hd { padding: .6rem 1rem .5rem; border-bottom: 1px solid #e2e8f0; margin-bottom: .25rem; }
        .dd-user-name { font-weight: 600; font-size: .875rem; color: #1e293b; }
        .dd-user-role { font-size: .73rem; color: #64748b; }

        /* ══════════════════════════════════════════
           ICON / AVATAR BUTTONS
        ══════════════════════════════════════════ */
        .icon-btn {
            width: 36px; height: 36px; border: none; background: none;
            cursor: pointer; color: rgba(255,255,255,.7); border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; transition: background .15s, color .15s;
            text-decoration: none;
        }
        .icon-btn:hover { background: rgba(255,255,255,.08); color: #fff; }

        .avatar-btn {
            display: flex; align-items: center; gap: .45rem;
            border: none; background: none; cursor: pointer;
            padding: .3rem .45rem; border-radius: 6px;
            color: rgba(255,255,255,.75); transition: background .15s;
        }
        .avatar-btn:hover, .avatar-btn.open { background: rgba(255,255,255,.08); }
        .avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: .68rem; font-weight: 700; color: #fff; flex-shrink: 0;
        }
        .avatar-name { font-size: .82rem; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* ══════════════════════════════════════════
           MOBILE DRAWER ITEMS
        ══════════════════════════════════════════ */
        .mob-label {
            font-size: .65rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .07em; color: rgba(255,255,255,.3);
            padding: .6rem .75rem .2rem;
        }
        .mob-link {
            display: flex; align-items: center; gap: .6rem;
            padding: .6rem .75rem;
            color: rgba(255,255,255,.8);
            text-decoration: none; border-radius: 8px;
            font-size: .9rem; transition: background .12s;
            margin: 1px .25rem;
        }
        .mob-link:hover, .mob-link.active { background: rgba(255,255,255,.08); color: #fff; }
        .mob-link i { font-size: .95rem; width: 1.2rem; text-align: center; }
        .mob-link.active i { color: #60a5fa; }
        .mob-link.danger { color: #f87171; }
        .mob-sep { height: 1px; background: rgba(255,255,255,.08); margin: .4rem .75rem; }

        /* Mobile accordion */
        .mob-accordion > .mob-acc-toggle {
            display: flex; align-items: center; gap: .6rem;
            padding: .6rem .75rem;
            color: rgba(255,255,255,.8);
            background: none; border: none; cursor: pointer;
            font-size: .9rem; font-family: inherit;
            width: 100%; text-align: left; border-radius: 8px;
            transition: background .12s; margin: 1px .25rem;
        }
        .mob-accordion > .mob-acc-toggle:hover { background: rgba(255,255,255,.08); color: #fff; }
        .mob-accordion > .mob-acc-toggle .acc-caret { margin-left: auto; font-size: .7rem; transition: transform .2s; }
        .mob-accordion.open > .mob-acc-toggle .acc-caret { transform: rotate(180deg); }
        .mob-acc-body { display: none; padding-left: 1.75rem; }
        .mob-accordion.open .mob-acc-body { display: block; }
        .mob-acc-sub {
            display: flex; align-items: center; gap: .6rem;
            padding: .5rem .5rem;
            color: rgba(255,255,255,.65);
            text-decoration: none; border-radius: 6px;
            font-size: .85rem; transition: background .1s;
        }
        .mob-acc-sub:hover, .mob-acc-sub.active { background: rgba(255,255,255,.07); color: #fff; }
        .mob-acc-sub.active { color: #93c5fd; }
        .mob-acc-sub i { font-size: .88rem; width: 1rem; text-align: center; }

        /* Mobile user header */
        .mob-user-hd {
            display: flex; align-items: center; gap: .75rem;
            padding: 1rem .75rem .75rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            margin-bottom: .4rem;
        }
        .mob-user-hd .avatar { width: 38px; height: 38px; font-size: .78rem; }
        .mob-user-name { font-weight: 600; color: #fff; font-size: .9rem; }
        .mob-user-role { font-size: .75rem; color: rgba(255,255,255,.45); }

        /* ══════════════════════════════════════════
           OVERLAY (mobile)
        ══════════════════════════════════════════ */
        .overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 998;
            opacity: 0;
            transition: opacity .22s;
        }
        .overlay.open { opacity: 1; }

        /* ══════════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════════ */
        @media (max-width: 768px) {
            .nav-desktop { display: none; }
            .nav-right-desktop { display: none; }
            .hamburger { display: flex; align-items: center; }
            .nav-mobile { display: block; }
            .overlay { display: block; }
        }
    </style>
</head>
<body class="bg-light">

@inject('moduleAccess', 'App\Services\ModuleAccessService')
@php
    $authUser = auth()->user();
    $initials = collect(explode(' ', $authUser->name))
        ->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->take(2)->implode('');

    $modules = \App\Modules\ModuleRegistry::active();

    $moduleRoutePatterns = ['checklist','categories.*','subcategories.*','activities.*',
        'purchase-requests.*','shifts.*','time-entries.*','work-schedules.*','estoque.*'];
    $modulesActive = request()->routeIs(...$moduleRoutePatterns);
    $adminActive   = request()->routeIs('admin.dashboard','admin.companies.*','admin.users.*','admin.audit-logs.*');

    $canClock = !$authUser->isSuperAdmin() && $authUser->company_id
                && $moduleAccess->canAccess($authUser, 'time_clock');
@endphp

{{-- ══ NAVBAR ══════════════════════════════════════════════════ --}}
<nav class="topbar">

    {{-- Brand --}}
    <a class="brand" href="{{ route('dashboard') }}">
        <span class="brand-icon"><i class="bi bi-grid-3x3-gap-fill"></i></span>
        <span>
            <span class="brand-name">UaiLabs</span>
            @if($authUser->company)
                <span class="brand-sub">{{ $authUser->company->name }}</span>
            @endif
        </span>
    </a>

    {{-- ── Desktop nav ── --}}
    <div class="nav-desktop">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        {{-- Módulos dropdown --}}
        <div class="dd-wrap">
            <button class="nav-link {{ $modulesActive ? 'active' : '' }}" id="btn-mod" onclick="ddToggle('dd-mod','btn-mod')">
                <i class="bi bi-grid"></i> Módulos
                <i class="bi bi-chevron-down caret"></i>
            </button>
            <div class="dd-menu" id="dd-mod">
                @foreach($modules as $mod)
                    @php $hasAccess = $authUser->isSuperAdmin() || $moduleAccess->canAccess($authUser, $mod['key']); @endphp

                    @if($mod['key'] === 'rotinas')
                        <div class="dd-header"><i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}</div>
                        @if($hasAccess)
                            <a class="dd-item {{ request()->routeIs('checklist') ? 'active' : '' }}" href="{{ route('checklist') }}">
                                <i class="bi bi-check2-square"></i> Checklist
                            </a>
                            @if($authUser->isAdminOrAbove())
                                <a class="dd-item {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                                    <i class="bi bi-folder2"></i> Categorias
                                </a>
                                <a class="dd-item {{ request()->routeIs('subcategories.*') ? 'active' : '' }}" href="{{ route('subcategories.index') }}">
                                    <i class="bi bi-folder2-open"></i> Subcategorias
                                </a>
                                <a class="dd-item {{ request()->routeIs('activities.*') ? 'active' : '' }}" href="{{ route('activities.index') }}">
                                    <i class="bi bi-list-task"></i> Atividades
                                </a>
                            @endif
                        @else
                            <span class="dd-item disabled"><i class="bi bi-lock"></i> Módulo inativo</span>
                        @endif
                        <div class="dd-sep"></div>

                    @elseif($mod['key'] === 'time_clock')
                        <div class="dd-header"><i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}</div>
                        @if($hasAccess)
                            <a class="dd-item {{ request()->routeIs('time-entries.dashboard') ? 'active' : '' }}" href="{{ route('time-entries.dashboard') }}">
                                <i class="bi bi-person-clock"></i> Meu Ponto
                            </a>
                            @if($authUser->isAdminOrAbove())
                                <a class="dd-item {{ request()->routeIs('time-entries.index') ? 'active' : '' }}" href="{{ route('time-entries.index') }}">
                                    <i class="bi bi-table"></i> Registros
                                </a>
                                <a class="dd-item {{ request()->routeIs('time-entries.monthly-report') ? 'active' : '' }}" href="{{ route('time-entries.monthly-report') }}">
                                    <i class="bi bi-file-bar-graph"></i> Relatório Mensal
                                </a>
                                <a class="dd-item {{ request()->routeIs('work-schedules.*') ? 'active' : '' }}" href="{{ route('work-schedules.index') }}">
                                    <i class="bi bi-calendar2-check"></i> Jornadas
                                </a>
                            @endif
                        @else
                            <span class="dd-item disabled"><i class="bi bi-lock"></i> Módulo inativo</span>
                        @endif
                        <div class="dd-sep"></div>

                    @else
                        {{-- Generic modules: purchase_requests, shifts, estoque, etc. --}}
                        @if($hasAccess)
                            <a class="dd-item {{ request()->routeIs(str_replace('.index','',$mod['route']).'*') ? 'active' : '' }}"
                               href="{{ route($mod['route']) }}">
                                <i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}
                            </a>
                        @else
                            <span class="dd-item disabled" title="Módulo inativo para sua conta">
                                <i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}
                                <i class="bi bi-lock-fill" style="font-size:.65rem;color:#cbd5e1;margin-left:auto"></i>
                            </span>
                        @endif
                    @endif
                @endforeach

                {{-- Audit Log (manager+ except superadmin) --}}
                @if($authUser->isManagerOrAbove() && !$authUser->isSuperAdmin())
                    <div class="dd-sep"></div>
                    @if($authUser->isAdmin())
                        <a class="dd-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                            <i class="bi bi-shield-check"></i> Audit Log
                        </a>
                    @else
                        <a class="dd-item {{ request()->routeIs('audit-log.index') ? 'active' : '' }}" href="{{ route('audit-log.index') }}">
                            <i class="bi bi-journal-text"></i> Log de Atividades
                        </a>
                    @endif
                @endif
            </div>
        </div>

        {{-- Admin dropdown (superadmin only) --}}
        @if($authUser->isSuperAdmin())
            <div class="nav-sep"></div>
            <div class="dd-wrap">
                <button class="nav-link {{ $adminActive ? 'active' : '' }}" id="btn-adm" onclick="ddToggle('dd-adm','btn-adm')">
                    <i class="bi bi-shield-lock"></i> Admin
                    <i class="bi bi-chevron-down caret"></i>
                </button>
                <div class="dd-menu" id="dd-adm">
                    <a class="dd-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-globe2"></i> Plataforma
                    </a>
                    <a class="dd-item {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}">
                        <i class="bi bi-building"></i> Empresas
                    </a>
                    <a class="dd-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people"></i> Usuários
                    </a>
                    <div class="dd-sep"></div>
                    <a class="dd-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                        <i class="bi bi-shield-check"></i> Audit Log
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- ── Desktop right ── --}}
    <div class="nav-right-desktop">
        @if($canClock)
            <a class="icon-btn" href="{{ url('/clock') }}" title="Bater Ponto">
                <i class="bi bi-fingerprint"></i>
            </a>
        @endif

        @if($authUser->isSuperAdmin())
            <a class="icon-btn" href="{{ route('admin.support-requests.index') }}" title="Solicitações de Suporte">
                <i class="bi bi-headset"></i>
            </a>
        @elseif($authUser->isManagerOrAbove() && $authUser->company_id)
            <a class="icon-btn" href="{{ route('support-requests.index') }}" title="Ajuda / Suporte">
                <i class="bi bi-headset"></i>
            </a>
        @endif

        <div class="dd-wrap">
            <button class="avatar-btn" id="btn-usr" onclick="ddToggle('dd-usr','btn-usr')">
                <span class="avatar">{{ $initials }}</span>
                <span class="avatar-name">{{ $authUser->name }}</span>
                <i class="bi bi-chevron-down" style="font-size:.6rem;color:rgba(255,255,255,.4);margin-left:.1rem"></i>
            </button>
            <div class="dd-menu dd-right" id="dd-usr">
                <div class="dd-user-hd">
                    <div class="dd-user-name">{{ $authUser->name }}</div>
                    <div class="dd-user-role">{{ ucfirst($authUser->role) }}</div>
                </div>
                <a class="dd-item" href="{{ route('password.edit') }}">
                    <i class="bi bi-key"></i> Redefinir senha
                </a>
                @if($authUser->company_id)
                    <a class="dd-item" href="{{ route('profile.pin.edit') }}">
                        <i class="bi bi-123"></i> Alterar PIN de Ponto
                    </a>
                @endif
                <div class="dd-sep"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dd-item danger" type="submit">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Hamburger (mobile) ── --}}
    <button class="hamburger" id="hamburger" onclick="mobileToggle()" aria-label="Menu">
        <i class="bi bi-list" id="ham-icon"></i>
    </button>
</nav>

{{-- Overlay --}}
<div class="overlay" id="overlay" onclick="mobileClose()"></div>

{{-- ══ MOBILE DRAWER ════════════════════════════════════════════ --}}
<div class="nav-mobile" id="nav-mobile">

    {{-- User header --}}
    <div class="mob-user-hd">
        <span class="avatar" style="width:38px;height:38px;font-size:.78rem">{{ $initials }}</span>
        <div>
            <div class="mob-user-name">{{ $authUser->name }}</div>
            <div class="mob-user-role">
                {{ ucfirst($authUser->role) }}@if($authUser->company) · {{ $authUser->company->name }}@endif
            </div>
        </div>
    </div>

    {{-- Dashboard --}}
    <a class="mob-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="mob-sep"></div>
    <div class="mob-label">Módulos</div>

    @foreach($modules as $mod)
        @php $hasAccess = $authUser->isSuperAdmin() || $moduleAccess->canAccess($authUser, $mod['key']); @endphp

        @if($mod['key'] === 'rotinas')
            @if($hasAccess)
                @php $rotinasOpen = request()->routeIs('checklist','categories.*','subcategories.*','activities.*'); @endphp
                <div class="mob-accordion {{ $rotinasOpen ? 'open' : '' }}" id="mob-rotinas">
                    <button class="mob-acc-toggle" onclick="mobAccordion('mob-rotinas')">
                        <i class="bi {{ $mod['icon'] }}"></i> Rotinas Operacionais
                        <i class="bi bi-chevron-down acc-caret"></i>
                    </button>
                    <div class="mob-acc-body">
                        <a class="mob-acc-sub {{ request()->routeIs('checklist') ? 'active' : '' }}" href="{{ route('checklist') }}">
                            <i class="bi bi-check2-square"></i> Checklist
                        </a>
                        @if($authUser->isAdminOrAbove())
                            <a class="mob-acc-sub {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                                <i class="bi bi-folder2"></i> Categorias
                            </a>
                            <a class="mob-acc-sub {{ request()->routeIs('subcategories.*') ? 'active' : '' }}" href="{{ route('subcategories.index') }}">
                                <i class="bi bi-folder2-open"></i> Subcategorias
                            </a>
                            <a class="mob-acc-sub {{ request()->routeIs('activities.*') ? 'active' : '' }}" href="{{ route('activities.index') }}">
                                <i class="bi bi-list-task"></i> Atividades
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <span class="mob-link" style="color:rgba(255,255,255,.3);cursor:default">
                    <i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}
                    <i class="bi bi-lock-fill" style="margin-left:auto;font-size:.7rem"></i>
                </span>
            @endif

        @elseif($mod['key'] === 'time_clock')
            @if($hasAccess)
                @php $pontoOpen = request()->routeIs('time-entries.*','work-schedules.*'); @endphp
                <div class="mob-accordion {{ $pontoOpen ? 'open' : '' }}" id="mob-ponto">
                    <button class="mob-acc-toggle" onclick="mobAccordion('mob-ponto')">
                        <i class="bi {{ $mod['icon'] }}"></i> Ponto
                        <i class="bi bi-chevron-down acc-caret"></i>
                    </button>
                    <div class="mob-acc-body">
                        <a class="mob-acc-sub {{ request()->routeIs('time-entries.dashboard') ? 'active' : '' }}" href="{{ route('time-entries.dashboard') }}">
                            <i class="bi bi-person-clock"></i> Meu Ponto
                        </a>
                        @if($authUser->isAdminOrAbove())
                            <a class="mob-acc-sub {{ request()->routeIs('time-entries.index') ? 'active' : '' }}" href="{{ route('time-entries.index') }}">
                                <i class="bi bi-table"></i> Registros
                            </a>
                            <a class="mob-acc-sub {{ request()->routeIs('time-entries.monthly-report') ? 'active' : '' }}" href="{{ route('time-entries.monthly-report') }}">
                                <i class="bi bi-file-bar-graph"></i> Relatório Mensal
                            </a>
                            <a class="mob-acc-sub {{ request()->routeIs('work-schedules.*') ? 'active' : '' }}" href="{{ route('work-schedules.index') }}">
                                <i class="bi bi-calendar2-check"></i> Jornadas
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <span class="mob-link" style="color:rgba(255,255,255,.3);cursor:default">
                    <i class="bi {{ $mod['icon'] }}"></i> Ponto
                    <i class="bi bi-lock-fill" style="margin-left:auto;font-size:.7rem"></i>
                </span>
            @endif

        @else
            {{-- Generic modules --}}
            @if($hasAccess)
                <a class="mob-link {{ request()->routeIs(str_replace('.index','',$mod['route']).'*') ? 'active' : '' }}"
                   href="{{ route($mod['route']) }}">
                    <i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}
                </a>
            @else
                <span class="mob-link" style="color:rgba(255,255,255,.3);cursor:default" title="Módulo inativo">
                    <i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}
                    <i class="bi bi-lock-fill" style="margin-left:auto;font-size:.7rem"></i>
                </span>
            @endif
        @endif
    @endforeach

    {{-- Audit Log (manager+ except superadmin) --}}
    @if($authUser->isManagerOrAbove() && !$authUser->isSuperAdmin())
        @if($authUser->isAdmin())
            <a class="mob-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                <i class="bi bi-shield-check"></i> Audit Log
            </a>
        @else
            <a class="mob-link {{ request()->routeIs('audit-log.index') ? 'active' : '' }}" href="{{ route('audit-log.index') }}">
                <i class="bi bi-journal-text"></i> Log de Atividades
            </a>
        @endif
    @endif

    {{-- Admin section (superadmin only) --}}
    @if($authUser->isSuperAdmin())
        <div class="mob-sep"></div>
        <div class="mob-label">Admin</div>
        <a class="mob-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-globe2"></i> Plataforma
        </a>
        <a class="mob-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}">
            <i class="bi bi-building"></i> Empresas
        </a>
        <a class="mob-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
            <i class="bi bi-people"></i> Usuários
        </a>
        <a class="mob-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
            <i class="bi bi-shield-check"></i> Audit Log
        </a>
        <a class="mob-link" href="{{ route('admin.support-requests.index') }}">
            <i class="bi bi-headset"></i> Suporte
        </a>
    @endif

    <div class="mob-sep"></div>

    @if($canClock)
        <a class="mob-link" href="{{ url('/clock') }}">
            <i class="bi bi-fingerprint"></i> Bater Ponto
        </a>
    @endif

    @if(!$authUser->isSuperAdmin() && $authUser->isManagerOrAbove() && $authUser->company_id)
        <a class="mob-link" href="{{ route('support-requests.index') }}">
            <i class="bi bi-headset"></i> Suporte
        </a>
    @endif

    <div class="mob-sep"></div>
    <a class="mob-link" href="{{ route('password.edit') }}">
        <i class="bi bi-key"></i> Redefinir senha
    </a>
    @if($authUser->company_id)
        <a class="mob-link" href="{{ route('profile.pin.edit') }}">
            <i class="bi bi-123"></i> Alterar PIN de Ponto
        </a>
    @endif
    <form method="POST" action="{{ route('logout') }}" style="margin:1px .25rem">
        @csrf
        <button type="submit" class="mob-link danger"
                style="border:none;background:none;width:calc(100% - .5rem);text-align:left;font-family:inherit;font-size:.9rem;cursor:pointer;border-radius:8px">
            <i class="bi bi-box-arrow-right"></i> Sair
        </button>
    </form>
</div>

{{-- ══ PAGE CONTENT ═══════════════════════════════════════════ --}}
<main class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @yield('content')
</main>

@stack('scripts')
<script>
/* ── DESKTOP DROPDOWNS ───────────────────────────────── */
function ddToggle(menuId, btnId) {
    var menu = document.getElementById(menuId);
    var btn  = document.getElementById(btnId);
    var open = menu.classList.contains('open');
    ddCloseAll();
    if (!open) {
        menu.classList.add('open');
        btn.classList.add('open');
    }
}
function ddCloseAll() {
    document.querySelectorAll('.dd-menu.open').forEach(function(m) { m.classList.remove('open'); });
    document.querySelectorAll('.nav-link.open, .avatar-btn.open').forEach(function(b) { b.classList.remove('open'); });
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dd-wrap')) ddCloseAll();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { ddCloseAll(); mobileClose(); }
});

/* ── MOBILE DRAWER ───────────────────────────────────── */
var _mobOpen = false;
function mobileToggle() { _mobOpen ? mobileClose() : mobileShow(); }
function mobileShow() {
    _mobOpen = true;
    document.getElementById('nav-mobile').classList.add('open');
    document.getElementById('overlay').classList.add('open');
    document.getElementById('ham-icon').className = 'bi bi-x-lg';
    document.body.style.overflow = 'hidden';
}
function mobileClose() {
    _mobOpen = false;
    document.getElementById('nav-mobile').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
    document.getElementById('ham-icon').className = 'bi bi-list';
    document.body.style.overflow = '';
}

/* ── MOBILE ACCORDION ────────────────────────────────── */
function mobAccordion(id) {
    document.getElementById(id).classList.toggle('open');
}
</script>
</body>
</html>