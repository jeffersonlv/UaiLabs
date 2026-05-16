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
    @stack('styles')
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
        'purchase-items.*','shifts.*','stations.*','time-entries.*','work-schedules.*','estoque.*'];
    $modulesActive = request()->routeIs(...$moduleRoutePatterns);
    $adminActive   = request()->routeIs('admin.dashboard','admin.companies.*','admin.users.*','admin.audit-logs.*');

@endphp

<nav class="navbar navbar-dark sticky-top" style="background:#1a1a2e;box-shadow:0 2px 8px rgba(0,0,0,.35)">
    <div class="container-fluid px-3">

        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="{{ route('dashboard') }}">
            <span class="d-flex align-items-center justify-content-center rounded-2"
                  style="width:32px;height:32px;background:linear-gradient(135deg,#3b82f6,#8b5cf6)">
                <i class="bi bi-grid-3x3-gap-fill text-white" style="font-size:.9rem"></i>
            </span>
            <span>
                <span class="fw-bold d-block" style="font-size:.92rem;line-height:1.1">UaiLabs</span>
                @if($authUser->company)
                    <span class="d-block" style="font-size:.62rem;color:rgba(255,255,255,.4)">{{ $authUser->company->name }}</span>
                @endif
            </span>
        </a>

        {{-- Desktop nav (hidden on mobile) --}}
        <div class="d-none d-md-flex align-items-center gap-1 flex-grow-1">

            <a class="nav-link text-white px-2 py-2 rounded-2 {{ request()->routeIs('dashboard') ? 'bg-white bg-opacity-10' : '' }}"
               href="{{ route('dashboard') }}" style="font-size:.875rem">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>

            {{-- Módulos dropdown --}}
            <div class="dropdown">
                <button class="btn btn-link nav-link text-white px-2 py-2 rounded-2 dropdown-toggle
                               {{ $modulesActive ? 'bg-white bg-opacity-10' : '' }}"
                        type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        style="font-size:.875rem;text-decoration:none">
                    <i class="bi bi-grid me-1"></i>Módulos
                </button>
                <ul class="dropdown-menu shadow-lg border-0 rounded-3 py-2" style="min-width:230px">
                    @foreach($modules as $mod)
                        @if(!($mod['menu'] ?? true)) @continue @endif
                        @php $hasAccess = $authUser->isSuperAdmin() || $moduleAccess->canAccess($authUser, $mod['key']); @endphp

                        @if($mod['key'] === 'rotinas')
                            <li><span class="dropdown-header text-uppercase" style="font-size:.67rem;letter-spacing:.06em">
                                <i class="bi {{ $mod['icon'] }} me-1"></i>{{ $mod['name'] }}
                            </span></li>
                            @if($hasAccess)
                                <li><a class="dropdown-item {{ request()->routeIs('checklist') ? 'active' : '' }}"
                                       href="{{ route('checklist') }}"><i class="bi bi-check2-square me-2"></i>Checklist</a></li>
                                @if($authUser->isAdminOrAbove())
                                    <li><a class="dropdown-item {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                                           href="{{ route('categories.index') }}"><i class="bi bi-folder2 me-2"></i>Categorias</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('subcategories.*') ? 'active' : '' }}"
                                           href="{{ route('subcategories.index') }}"><i class="bi bi-folder2-open me-2"></i>Subcategorias</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('activities.*') ? 'active' : '' }}"
                                           href="{{ route('activities.index') }}"><i class="bi bi-list-task me-2"></i>Atividades</a></li>
                                @endif
                            @else
                                <li><span class="dropdown-item disabled text-muted"><i class="bi bi-lock me-2"></i>Módulo inativo</span></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>

                        @elseif($mod['key'] === 'time_clock')
                            <li><span class="dropdown-header text-uppercase" style="font-size:.67rem;letter-spacing:.06em">
                                <i class="bi {{ $mod['icon'] }} me-1"></i>{{ $mod['name'] }}
                            </span></li>
                            @if($hasAccess)
                                <li><a class="dropdown-item {{ request()->routeIs('time-entries.dashboard') ? 'active' : '' }}"
                                       href="{{ route('time-entries.dashboard') }}"><i class="bi bi-person-clock me-2"></i>Meu Ponto</a></li>
                                @if($authUser->isAdminOrAbove())
                                    <li><a class="dropdown-item {{ request()->routeIs('time-entries.index') ? 'active' : '' }}"
                                           href="{{ route('time-entries.index') }}"><i class="bi bi-table me-2"></i>Registros</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('time-entries.monthly-report') ? 'active' : '' }}"
                                           href="{{ route('time-entries.monthly-report') }}"><i class="bi bi-file-bar-graph me-2"></i>Relatório Mensal</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('work-schedules.*') ? 'active' : '' }}"
                                           href="{{ route('work-schedules.index') }}"><i class="bi bi-calendar2-check me-2"></i>Jornadas</a></li>
                                @endif
                            @else
                                <li><span class="dropdown-item disabled text-muted"><i class="bi bi-lock me-2"></i>Módulo inativo</span></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>

                        @else
                            @if($hasAccess)
                                <li><a class="dropdown-item" href="{{ route($mod['route']) }}">
                                    <i class="bi {{ $mod['icon'] }} me-2"></i>{{ $mod['name'] }}
                                </a></li>
                            @else
                                <li><span class="dropdown-item disabled text-muted">
                                    <i class="bi {{ $mod['icon'] }} me-2"></i>{{ $mod['name'] }}
                                    <i class="bi bi-lock-fill ms-auto" style="font-size:.65rem"></i>
                                </span></li>
                            @endif
                        @endif
                    @endforeach

                    @if($authUser->isManagerOrAbove() && !$authUser->isSuperAdmin())
                        <li><hr class="dropdown-divider"></li>
                        @if($authUser->isAdmin())
                            <li><a class="dropdown-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
                                   href="{{ route('admin.audit-logs.index') }}"><i class="bi bi-shield-check me-2"></i>Audit Log</a></li>
                        @else
                            <li><a class="dropdown-item {{ request()->routeIs('audit-log.index') ? 'active' : '' }}"
                                   href="{{ route('audit-log.index') }}"><i class="bi bi-journal-text me-2"></i>Log de Atividades</a></li>
                        @endif
                    @endif
                </ul>
            </div>

            {{-- Admin dropdown (superadmin only) --}}
            @if($authUser->isSuperAdmin())
                <div class="dropdown">
                    <button class="btn btn-link nav-link text-white px-2 py-2 rounded-2 dropdown-toggle
                                   {{ $adminActive ? 'bg-white bg-opacity-10' : '' }}"
                            type="button" data-bs-toggle="dropdown"
                            style="font-size:.875rem;text-decoration:none">
                        <i class="bi bi-shield-lock me-1"></i>Admin
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0 rounded-3 py-2">
                        <li><a class="dropdown-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                               href="{{ route('admin.dashboard') }}"><i class="bi bi-globe2 me-2"></i>Plataforma</a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}"
                               href="{{ route('admin.companies.index') }}"><i class="bi bi-building me-2"></i>Empresas</a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                               href="{{ route('admin.users.index') }}"><i class="bi bi-people me-2"></i>Usuários</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
                               href="{{ route('admin.audit-logs.index') }}"><i class="bi bi-shield-check me-2"></i>Audit Log</a></li>
                    </ul>
                </div>
            @endif
        </div>

        {{-- Desktop right --}}
        <div class="d-none d-md-flex align-items-center gap-1 ms-auto">
            @if($authUser->isSuperAdmin())
                <a class="btn btn-link text-white p-2" href="{{ route('admin.support-requests.index') }}" title="Suporte">
                    <i class="bi bi-headset fs-5"></i>
                </a>
            @elseif($authUser->isManagerOrAbove() && $authUser->company_id)
                <a class="btn btn-link text-white p-2" href="{{ route('support-requests.index') }}" title="Suporte">
                    <i class="bi bi-headset fs-5"></i>
                </a>
            @endif

            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center gap-2 text-white text-decoration-none p-2 rounded-2"
                        type="button" data-bs-toggle="dropdown">
                    <span class="d-flex align-items-center justify-content-center rounded-circle fw-bold"
                          style="width:30px;height:30px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);font-size:.68rem">
                        {{ $initials }}
                    </span>
                    <span style="font-size:.82rem;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $authUser->name }}
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:.6rem;color:rgba(255,255,255,.4)"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 py-2" style="min-width:200px">
                    <li class="px-3 py-2 border-bottom mb-1">
                        <div class="fw-semibold" style="font-size:.875rem">{{ $authUser->name }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ ucfirst($authUser->role) }}</div>
                    </li>
                    <li><a class="dropdown-item" href="{{ route('password.edit') }}">
                        <i class="bi bi-key me-2"></i>Alterar senha
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('profile.pin.edit') }}">
                        <i class="bi bi-shield-lock me-2"></i>Alterar PIN
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Sair
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Hamburger (mobile only) --}}
        <button class="navbar-toggler d-md-none border-0 ms-auto" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#mobileDrawer">
            <i class="bi bi-list text-white fs-4"></i>
        </button>
    </div>
</nav>

{{-- ══ MOBILE OFFCANVAS DRAWER ════════════════════════════════ --}}
<div class="offcanvas offcanvas-start text-white d-md-none"
     style="background:#1a1a2e;width:280px" tabindex="-1" id="mobileDrawer">
    <div class="offcanvas-header border-bottom" style="border-color:rgba(255,255,255,.1)!important">
        <div class="d-flex align-items-center gap-2">
            <span class="d-flex align-items-center justify-content-center rounded-circle fw-bold"
                  style="width:38px;height:38px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);font-size:.78rem">
                {{ $initials }}
            </span>
            <div>
                <div class="fw-semibold" style="font-size:.9rem">{{ $authUser->name }}</div>
                <div style="font-size:.75rem;color:rgba(255,255,255,.45)">
                    {{ ucfirst($authUser->role) }}@if($authUser->company) · {{ $authUser->company->name }}@endif
                </div>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-2">

        <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1
                  {{ request()->routeIs('dashboard') ? 'bg-white bg-opacity-10 text-white' : '' }}"
           style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2" style="width:1.2rem;text-align:center"></i>Dashboard
        </a>

        <div class="px-2 pt-2 pb-1" style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.3)">
            Módulos
        </div>

        @foreach($modules as $mod)
            @if(!($mod['menu'] ?? true)) @continue @endif
            @php $hasAccess = $authUser->isSuperAdmin() || $moduleAccess->canAccess($authUser, $mod['key']); @endphp

            @if($mod['key'] === 'rotinas')
                @if($hasAccess)
                    @php $rotinasOpen = request()->routeIs('checklist','categories.*','subcategories.*','activities.*'); @endphp
                    <div class="mb-1">
                        <button class="d-flex align-items-center gap-2 w-100 px-3 py-2 rounded-2 border-0 text-start"
                                style="background:none;color:rgba(255,255,255,.8);font-size:.9rem;font-family:inherit;cursor:pointer"
                                data-bs-toggle="collapse" data-bs-target="#mob-rotinas">
                            <i class="bi {{ $mod['icon'] }}" style="width:1.2rem;text-align:center"></i>
                            Rotinas Operacionais
                            <i class="bi bi-chevron-down ms-auto" style="font-size:.7rem"></i>
                        </button>
                        <div class="collapse ps-4 {{ $rotinasOpen ? 'show' : '' }}" id="mob-rotinas">
                            <a class="d-flex align-items-center gap-2 px-2 py-2 rounded-2 text-decoration-none
                                      {{ request()->routeIs('checklist') ? 'text-white' : '' }}"
                               style="color:rgba(255,255,255,.65);font-size:.85rem" href="{{ route('checklist') }}">
                                <i class="bi bi-check2-square"></i>Checklist
                            </a>
                            @if($authUser->isAdminOrAbove())
                                <a class="d-flex align-items-center gap-2 px-2 py-2 rounded-2 text-decoration-none"
                                   style="color:rgba(255,255,255,.65);font-size:.85rem" href="{{ route('categories.index') }}">
                                    <i class="bi bi-folder2"></i>Categorias
                                </a>
                                <a class="d-flex align-items-center gap-2 px-2 py-2 rounded-2 text-decoration-none"
                                   style="color:rgba(255,255,255,.65);font-size:.85rem" href="{{ route('subcategories.index') }}">
                                    <i class="bi bi-folder2-open"></i>Subcategorias
                                </a>
                                <a class="d-flex align-items-center gap-2 px-2 py-2 rounded-2 text-decoration-none"
                                   style="color:rgba(255,255,255,.65);font-size:.85rem" href="{{ route('activities.index') }}">
                                    <i class="bi bi-list-task"></i>Atividades
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

            @elseif($mod['key'] === 'time_clock')
                @if($hasAccess)
                    @php $pontoOpen = request()->routeIs('time-entries.*','work-schedules.*'); @endphp
                    <div class="mb-1">
                        <button class="d-flex align-items-center gap-2 w-100 px-3 py-2 rounded-2 border-0 text-start"
                                style="background:none;color:rgba(255,255,255,.8);font-size:.9rem;font-family:inherit;cursor:pointer"
                                data-bs-toggle="collapse" data-bs-target="#mob-ponto">
                            <i class="bi {{ $mod['icon'] }}" style="width:1.2rem;text-align:center"></i>
                            Ponto
                            <i class="bi bi-chevron-down ms-auto" style="font-size:.7rem"></i>
                        </button>
                        <div class="collapse ps-4 {{ $pontoOpen ? 'show' : '' }}" id="mob-ponto">
                            <a class="d-flex align-items-center gap-2 px-2 py-2 rounded-2 text-decoration-none"
                               style="color:rgba(255,255,255,.65);font-size:.85rem" href="{{ route('time-entries.dashboard') }}">
                                <i class="bi bi-person-clock"></i>Meu Ponto
                            </a>
                            @if($authUser->isAdminOrAbove())
                                <a class="d-flex align-items-center gap-2 px-2 py-2 rounded-2 text-decoration-none"
                                   style="color:rgba(255,255,255,.65);font-size:.85rem" href="{{ route('time-entries.index') }}">
                                    <i class="bi bi-table"></i>Registros
                                </a>
                                <a class="d-flex align-items-center gap-2 px-2 py-2 rounded-2 text-decoration-none"
                                   style="color:rgba(255,255,255,.65);font-size:.85rem" href="{{ route('time-entries.monthly-report') }}">
                                    <i class="bi bi-file-bar-graph"></i>Relatório Mensal
                                </a>
                                <a class="d-flex align-items-center gap-2 px-2 py-2 rounded-2 text-decoration-none"
                                   style="color:rgba(255,255,255,.65);font-size:.85rem" href="{{ route('work-schedules.index') }}">
                                    <i class="bi bi-calendar2-check"></i>Jornadas
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

            @else
                @if($hasAccess)
                    <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
                       style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route($mod['route']) }}">
                        <i class="bi {{ $mod['icon'] }}" style="width:1.2rem;text-align:center"></i>{{ $mod['name'] }}
                    </a>
                @endif
            @endif
        @endforeach

        @if($authUser->isManagerOrAbove() && !$authUser->isSuperAdmin())
            <hr style="border-color:rgba(255,255,255,.1)">
            @if($authUser->isAdmin())
                <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
                   style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('admin.audit-logs.index') }}">
                    <i class="bi bi-shield-check" style="width:1.2rem;text-align:center"></i>Audit Log
                </a>
            @else
                <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
                   style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('audit-log.index') }}">
                    <i class="bi bi-journal-text" style="width:1.2rem;text-align:center"></i>Log de Atividades
                </a>
            @endif
        @endif

        @if($authUser->isSuperAdmin())
            <hr style="border-color:rgba(255,255,255,.1)">
            <div class="px-2 pb-1" style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.3)">Admin</div>
            <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
               style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-globe2" style="width:1.2rem;text-align:center"></i>Plataforma
            </a>
            <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
               style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('admin.companies.index') }}">
                <i class="bi bi-building" style="width:1.2rem;text-align:center"></i>Empresas
            </a>
            <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
               style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('admin.users.index') }}">
                <i class="bi bi-people" style="width:1.2rem;text-align:center"></i>Usuários
            </a>
            <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
               style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('admin.audit-logs.index') }}">
                <i class="bi bi-shield-check" style="width:1.2rem;text-align:center"></i>Audit Log
            </a>
            <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
               style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('admin.support-requests.index') }}">
                <i class="bi bi-headset" style="width:1.2rem;text-align:center"></i>Suporte
            </a>
        @endif

        <hr style="border-color:rgba(255,255,255,.1)">


        @if(!$authUser->isSuperAdmin() && $authUser->isManagerOrAbove() && $authUser->company_id)
            <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
               style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('support-requests.index') }}">
                <i class="bi bi-headset" style="width:1.2rem;text-align:center"></i>Suporte
            </a>
        @endif

        <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
           style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('password.edit') }}">
            <i class="bi bi-key" style="width:1.2rem;text-align:center"></i>Alterar senha
        </a>
        <a class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none mb-1"
           style="color:rgba(255,255,255,.8);font-size:.9rem" href="{{ route('profile.pin.edit') }}">
            <i class="bi bi-shield-lock" style="width:1.2rem;text-align:center"></i>Alterar PIN
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="d-flex align-items-center gap-2 w-100 px-3 py-2 rounded-2 border-0 text-start"
                    style="background:none;color:#f87171;font-size:.9rem;font-family:inherit;cursor:pointer">
                <i class="bi bi-box-arrow-right" style="width:1.2rem;text-align:center"></i>Sair
            </button>
        </form>
    </div>
</div>

{{-- ══ PAGE CONTENT ══════════════════════════════════════════ --}}
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

{{-- Scripts ficam DEPOIS do @vite para garantir que Bootstrap está disponível --}}
@stack('scripts')
</body>
</html>
