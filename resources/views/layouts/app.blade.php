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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        .navbar-brand-logo {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            text-decoration: none;
        }
        .navbar-brand-logo .brand-icon {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            color: #fff;
            flex-shrink: 0;
        }
        .navbar-brand-logo .brand-company {
            font-size: .68rem;
            font-weight: 400;
            color: rgba(255,255,255,.5);
            display: block;
            line-height: 1;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .navbar-brand-logo .brand-name {
            line-height: 1.15;
            display: block;
        }
        .navbar-nav .nav-link { color: rgba(255,255,255,.78); padding: .5rem .7rem; }
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active  { color: #fff; }
        .navbar-nav .nav-link .bi { font-size: .9rem; vertical-align: -.08em; }
        .dropdown-item .bi { width: 1.2rem; text-align: center; vertical-align: -.08em; }
        .dropdown-item.disabled { opacity: .45; cursor: default; }
        .dropdown-header { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: #6c757d; padding: .45rem 1rem .2rem; }
        .nav-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .nav-divider-v { width: 1px; background: rgba(255,255,255,.15); margin: .35rem .2rem; align-self: stretch; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 py-2">
    @inject('moduleAccess', 'App\Services\ModuleAccessService')
    @php
        $authUser = auth()->user();
        $initials = collect(explode(' ', $authUser->name))
            ->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->take(2)->implode('');

        $modules = \App\Modules\ModuleRegistry::active();

        // Is any module route currently active?
        $moduleRoutePatterns = ['checklist','categories.*','subcategories.*','activities.*',
            'purchase-requests.*','shifts.*','time-entries.*','work-schedules.*','estoque.*'];
        $modulesActive = request()->routeIs(...$moduleRoutePatterns);

        // Is any admin route active?
        $adminActive = request()->routeIs('admin.dashboard','admin.companies.*','admin.users.*','admin.audit-logs.*');
    @endphp

    {{-- Brand --}}
    <a class="navbar-brand-logo me-3" href="{{ route('dashboard') }}">
        <span class="brand-icon"><i class="bi bi-grid-3x3-gap-fill"></i></span>
        <span>
            <span class="brand-name">UaiLabs</span>
            @if($authUser->company)
                <span class="brand-company">{{ $authUser->company->name }}</span>
            @endif
        </span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav me-auto align-items-lg-center">

            {{-- Dashboard --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>

            {{-- ── Dropdown Módulos ─────────────────────────────── --}}
            <li class="nav-item dropdown">
                <button type="button"
                        class="btn btn-link nav-link dropdown-toggle {{ $modulesActive ? 'active' : '' }}"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-grid"></i> Módulos
                </button>
                <ul class="dropdown-menu shadow" style="min-width:230px">
                    @foreach($modules as $mod)
                        @php $hasAccess = $authUser->isSuperAdmin() || $moduleAccess->canAccess($authUser, $mod['key']); @endphp

                        @if($mod['key'] === 'rotinas')
                            <li><span class="dropdown-header"><i class="bi {{ $mod['icon'] }} me-1"></i>{{ $mod['name'] }}</span></li>
                            @if($hasAccess)
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('checklist') ? 'active' : '' }}"
                                       href="{{ route('checklist') }}">
                                        <i class="bi bi-check2-square"></i> Checklist
                                    </a>
                                </li>
                                @if($authUser->isAdminOrAbove())
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                                           href="{{ route('categories.index') }}">
                                            <i class="bi bi-folder2"></i> Categorias
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('subcategories.*') ? 'active' : '' }}"
                                           href="{{ route('subcategories.index') }}">
                                            <i class="bi bi-folder2-open"></i> Subcategorias
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('activities.*') ? 'active' : '' }}"
                                           href="{{ route('activities.index') }}">
                                            <i class="bi bi-list-task"></i> Atividades
                                        </a>
                                    </li>
                                @endif
                            @else
                                <li>
                                    <span class="dropdown-item disabled">
                                        <i class="bi bi-lock me-1"></i> Módulo inativo
                                    </span>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider my-1"></li>

                        @elseif($mod['key'] === 'time_clock')
                            <li><span class="dropdown-header"><i class="bi {{ $mod['icon'] }} me-1"></i>{{ $mod['name'] }}</span></li>
                            @if($hasAccess)
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('time-entries.dashboard') ? 'active' : '' }}"
                                       href="{{ route('time-entries.dashboard') }}">
                                        <i class="bi bi-person-clock"></i> Meu Ponto
                                    </a>
                                </li>
                                @if($authUser->isAdminOrAbove())
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('time-entries.index') ? 'active' : '' }}"
                                           href="{{ route('time-entries.index') }}">
                                            <i class="bi bi-table"></i> Registros
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('time-entries.monthly-report') ? 'active' : '' }}"
                                           href="{{ route('time-entries.monthly-report') }}">
                                            <i class="bi bi-file-bar-graph"></i> Relatório Mensal
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('work-schedules.*') ? 'active' : '' }}"
                                           href="{{ route('work-schedules.index') }}">
                                            <i class="bi bi-calendar2-check"></i> Jornadas
                                        </a>
                                    </li>
                                @endif
                            @else
                                <li>
                                    <span class="dropdown-item disabled">
                                        <i class="bi bi-lock me-1"></i> Módulo inativo
                                    </span>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider my-1"></li>

                        @else
                            {{-- Generic module (purchase_requests, shifts, estoque) --}}
                            @if($hasAccess)
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs(str_replace('.index','',$mod['route']).'*') ? 'active' : '' }}"
                                       href="{{ route($mod['route']) }}">
                                        <i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}
                                    </a>
                                </li>
                            @else
                                <li>
                                    <span class="dropdown-item disabled" title="Módulo inativo para sua conta">
                                        <i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}
                                        <i class="bi bi-lock-fill ms-1" style="font-size:.7rem"></i>
                                    </span>
                                </li>
                            @endif
                        @endif
                    @endforeach

                    {{-- Log / Audit Log (manager+ exceto superadmin) --}}
                    @if($authUser->isManagerOrAbove() && !$authUser->isSuperAdmin())
                        @if($authUser->isAdmin())
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
                                   href="{{ route('admin.audit-logs.index') }}">
                                    <i class="bi bi-shield-check"></i> Audit Log
                                </a>
                            </li>
                        @else
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('audit-log.index') ? 'active' : '' }}"
                                   href="{{ route('audit-log.index') }}">
                                    <i class="bi bi-journal-text"></i> Log de Atividades
                                </a>
                            </li>
                        @endif
                    @endif
                </ul>
            </li>

            {{-- ── Admin (superadmin) ───────────────────────────── --}}
            @if($authUser->isSuperAdmin())
                <li class="d-none d-lg-flex nav-divider-v"></li>
                <li class="nav-item dropdown">
                    <button type="button"
                            class="btn btn-link nav-link dropdown-toggle {{ $adminActive ? 'active' : '' }}"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-shield-lock"></i> Admin
                    </button>
                    <ul class="dropdown-menu shadow">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                               href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-globe2"></i> Plataforma
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}"
                               href="{{ route('admin.companies.index') }}">
                                <i class="bi bi-building"></i> Empresas
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                               href="{{ route('admin.users.index') }}">
                                <i class="bi bi-people"></i> Usuários
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
                               href="{{ route('admin.audit-logs.index') }}">
                                <i class="bi bi-shield-check"></i> Audit Log
                            </a>
                        </li>
                    </ul>
                </li>
            @endif


        </ul>

        {{-- Right side --}}
        <ul class="navbar-nav align-items-lg-center gap-1">

            {{-- Bater ponto rápido --}}
            @if(!$authUser->isSuperAdmin() && $authUser->company_id && $moduleAccess->canAccess($authUser, 'time_clock'))
                <li class="nav-item">
                    <a class="nav-link px-2" href="{{ url('/clock') }}" title="Bater Ponto">
                        <i class="bi bi-fingerprint" style="font-size:1.2rem"></i>
                    </a>
                </li>
            @endif

            {{-- Suporte --}}
            @if($authUser->isSuperAdmin())
                <li class="nav-item">
                    <a class="nav-link px-2" href="{{ route('admin.support-requests.index') }}" title="Solicitações de Suporte">
                        <i class="bi bi-headset" style="font-size:1.1rem"></i>
                    </a>
                </li>
            @elseif($authUser->isManagerOrAbove() && $authUser->company_id)
                <li class="nav-item">
                    <a class="nav-link px-2" href="{{ route('support-requests.index') }}" title="Ajuda / Suporte">
                        <i class="bi bi-headset" style="font-size:1.1rem"></i>
                    </a>
                </li>
            @endif

            {{-- User dropdown --}}
            <li class="nav-item dropdown ms-1">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 pe-0"
                   href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-avatar">{{ $initials }}</span>
                    <span class="d-none d-xl-inline text-white-50"
                          style="font-size:.85rem;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $authUser->name }}
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:210px">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-semibold" style="font-size:.85rem">{{ $authUser->name }}</div>
                        <div class="text-muted" style="font-size:.75rem">{{ ucfirst($authUser->role) }}</div>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('password.edit') }}">
                            <i class="bi bi-key me-2 text-secondary"></i> Redefinir senha
                        </a>
                    </li>
                    @if($authUser->company_id)
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('profile.pin.edit') }}">
                                <i class="bi bi-123 me-2 text-secondary"></i> Alterar PIN de Ponto
                            </a>
                        </li>
                    @endif
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item py-2 text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Sair
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

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
(function () {
    function initDropdowns() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (el) {
                new bootstrap.Dropdown(el);
            });
            return;
        }
        // Fallback: vanilla JS toggle for when Bootstrap JS fails to load
        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (toggle) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var parent = toggle.closest('.dropdown');
                if (!parent) return;
                var menu = parent.querySelector('.dropdown-menu');
                if (!menu) return;
                var open = menu.classList.contains('show');
                document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                    m.classList.remove('show');
                    m.previousElementSibling && m.previousElementSibling.setAttribute('aria-expanded', 'false');
                });
                if (!open) {
                    menu.classList.add('show');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            });
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                    m.classList.remove('show');
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDropdowns);
    } else {
        initDropdowns();
    }
})();
</script>
</body>
</html>