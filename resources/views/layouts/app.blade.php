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
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            color: #fff;
            flex-shrink: 0;
        }
        .navbar-brand-logo .brand-company {
            font-size: .7rem;
            font-weight: 400;
            color: rgba(255,255,255,.55);
            display: block;
            line-height: 1;
        }
        .navbar-brand-logo .brand-name {
            line-height: 1.1;
            display: block;
        }
        .nav-link .bi { font-size: .9rem; }
        .nav-link.active { color: #fff !important; }
        .navbar-nav .nav-link { color: rgba(255,255,255,.75); padding: .5rem .65rem; }
        .navbar-nav .nav-link:hover { color: #fff; }
        .dropdown-item .bi { width: 1.1rem; text-align: center; }
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
        .module-locked { cursor: default; opacity: .45; }
        .nav-divider { width: 1px; background: rgba(255,255,255,.15); margin: .3rem .25rem; align-self: stretch; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 py-2">
    @inject('moduleAccess', 'App\Services\ModuleAccessService')
    @php
        $authUser    = auth()->user();
        $initials    = collect(explode(' ', $authUser->name))->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');
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
        {{-- Main nav --}}
        <ul class="navbar-nav me-auto align-items-lg-center">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>

            @foreach(\App\Modules\ModuleRegistry::active() as $mod)
                @php $hasAccess = $authUser->isSuperAdmin() || $moduleAccess->canAccess($authUser, $mod['key']); @endphp
                @if($hasAccess)
                    @if($mod['key'] === 'rotinas' && $authUser->isAdminOrAbove())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('checklist','categories.*','subcategories.*','activities.*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi {{ $mod['icon'] }}"></i>
                                <span class="d-lg-none d-xl-inline">{{ $mod['name'] }}</span>
                                <span class="d-none d-lg-inline d-xl-none">Rotinas</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('checklist') ? 'active' : '' }}"
                                       href="{{ route('checklist') }}">
                                        <i class="bi bi-check2-square"></i> Checklist
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
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
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs($mod['route']) ? 'active' : '' }}"
                               href="{{ route($mod['route']) }}">
                                <i class="bi {{ $mod['icon'] }}"></i>
                                <span class="d-lg-none d-xl-inline">{{ $mod['name'] }}</span>
                                <span class="d-none d-lg-inline d-xl-none">
                                    {{ Str::words($mod['name'], 1, '') }}
                                </span>
                            </a>
                        </li>
                    @endif
                @else
                    <li class="nav-item">
                        <span class="nav-link module-locked" title="Módulo inativo para sua conta">
                            <i class="bi {{ $mod['icon'] }}"></i>
                            <span class="d-lg-none d-xl-inline">{{ $mod['name'] }}</span>
                            <span class="d-none d-lg-inline d-xl-none">
                                {{ Str::words($mod['name'], 1, '') }}
                            </span>
                            <i class="bi bi-lock-fill" style="font-size:.65rem;opacity:.6"></i>
                        </span>
                    </li>
                @endif
            @endforeach

            @if($authUser->isManagerOrAbove() && !$authUser->isSuperAdmin())
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('audit-log.index') ? 'active' : '' }}"
                       href="{{ route('audit-log.index') }}">
                        <i class="bi bi-journal-text"></i>
                        <span class="d-lg-none d-xl-inline">Log de Atividades</span>
                        <span class="d-none d-lg-inline d-xl-none">Log</span>
                    </a>
                </li>
            @endif

            @if($authUser->isSuperAdmin())
                <li class="d-none d-lg-flex nav-divider"></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}" title="Painel da Plataforma">
                        <i class="bi bi-globe2"></i> Plataforma
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}"
                       href="{{ route('admin.companies.index') }}" title="Empresas">
                        <i class="bi bi-building"></i> Empresas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                       href="{{ route('admin.users.index') }}" title="Usuários">
                        <i class="bi bi-people"></i> Usuários
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
                       href="{{ route('admin.audit-logs.index') }}" title="Audit Log">
                        <i class="bi bi-shield-check"></i> Audit Log
                    </a>
                </li>
            @endif

            @if($authUser->isAdmin())
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
                       href="{{ route('admin.audit-logs.index') }}">
                        <i class="bi bi-shield-check"></i>
                        <span class="d-lg-none d-xl-inline">Audit Log</span>
                        <span class="d-none d-lg-inline d-xl-none">Audit</span>
                    </a>
                </li>
            @endif
        </ul>

        {{-- Right side --}}
        <ul class="navbar-nav align-items-lg-center gap-1">

            {{-- Bater ponto rápido (staff/manager with time_clock) --}}
            @if(!$authUser->isSuperAdmin() && $authUser->company_id && $moduleAccess->canAccess($authUser, 'time_clock'))
                <li class="nav-item">
                    <a class="nav-link px-2" href="{{ url('/clock') }}" title="Bater Ponto">
                        <i class="bi bi-fingerprint" style="font-size:1.15rem"></i>
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
                    <a class="nav-link px-2" href="{{ route('support-requests.index') }}" title="Solicitações / Ajuda">
                        <i class="bi bi-headset" style="font-size:1.1rem"></i>
                    </a>
                </li>
            @endif

            {{-- User dropdown --}}
            <li class="nav-item dropdown ms-1">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 pe-0"
                   href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-avatar">{{ $initials }}</span>
                    <span class="d-none d-lg-inline text-white-50" style="font-size:.85rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $authUser->name }}
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:200px">
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
</body>
</html>