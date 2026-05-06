<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UaiLabs</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    @inject('moduleAccess', 'App\Services\ModuleAccessService')
    @php $authUser = auth()->user(); @endphp
    <a class="navbar-brand fw-semibold" href="{{ route('dashboard') }}">
        @if($authUser->company)
            <span class="text-white-50 fw-normal" style="font-size:.75rem">{{ $authUser->company->name }}</span>
        @endif
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>

            @foreach(\App\Modules\ModuleRegistry::active() as $mod)
                @php $hasAccess = $authUser->isSuperAdmin() || $moduleAccess->canAccess($authUser, $mod['key']); @endphp
                @if($hasAccess)
                    @if($mod['key'] === 'rotinas' && $authUser->isAdminOrAbove())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('checklist') }}">Checklist</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('categories.index') }}">Categorias</a></li>
                                <li><a class="dropdown-item" href="{{ route('activities.index') }}">Atividades</a></li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route($mod['route']) }}">
                                <i class="bi {{ $mod['icon'] }}"></i> {{ $mod['name'] }}
                            </a>
                        </li>
                    @endif
                @else
                    <li class="nav-item">
                        <span class="nav-link text-white-50" title="Módulo inativo para sua conta" style="cursor:default">
                            <i class="bi bi-lock-fill"></i> {{ $mod['name'] }}
                        </span>
                    </li>
                @endif
            @endforeach

            @if($authUser->isAdmin())
                <li class="nav-item"><a class="nav-link" href="{{ route('units.index') }}">Filiais</a></li>
            @endif
            @if($authUser->isManagerOrAbove())
                <li class="nav-item"><a class="nav-link" href="{{ route('audit-log.index') }}">Log de Atividades</a></li>
            @endif
            @if($authUser->isSuperAdmin())
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.companies.index') }}">Empresas</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Usuários</a></li>
            @endif
        </ul>
        <ul class="navbar-nav align-items-center gap-1">
            {{-- Botão de ajuda / Solicitações --}}
            @if($authUser->isSuperAdmin())
                <li class="nav-item">
                    <a class="nav-link px-2" href="{{ route('admin.support-requests.index') }}"
                       title="Solicitações">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                             viewBox="0 0 16 16" style="vertical-align:-.15em">
                            <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
                            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8z"/>
                        </svg>
                    </a>
                </li>
            @elseif($authUser->isManagerOrAbove() && $authUser->company_id)
                <li class="nav-item">
                    <a class="nav-link px-2" href="{{ route('support-requests.index') }}"
                       title="Solicitações / Ajuda">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                             viewBox="0 0 16 16" style="vertical-align:-.15em">
                            <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
                            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8z"/>
                        </svg>
                    </a>
                </li>
            @endif

            {{-- Dropdown do usuário --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-secondary" href="#" role="button"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    {{ auth()->user()->name }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('password.edit') }}">
                            &#128273; Redefinir senha
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger">Sair</button>
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
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @yield('content')
</main>

@stack('scripts')
</body>
</html>
