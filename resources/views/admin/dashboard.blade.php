@extends('layouts.app')
@section('title', 'Painel Global')
@section('content')
<h4 class="mb-4"><i class="bi bi-globe me-2"></i>Painel Global da Plataforma</h4>

<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Empresas totais</div>
            <div class="fw-bold fs-3">{{ $totalCompanies }}</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Empresas ativas</div>
            <div class="fw-bold fs-3 text-success">{{ $activeCompanies }}</div>
        </div>
    </div>
    @foreach($usersByRole as $role => $count)
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">{{ ucfirst($role) }}s</div>
            <div class="fw-bold fs-3">{{ $count }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Módulos disponíveis</div>
            <ul class="list-group list-group-flush">
                @foreach($moduleSummary as $mod)
                <li class="list-group-item d-flex align-items-center gap-2">
                    <i class="bi {{ $mod['icon'] }} text-primary"></i>
                    <span>{{ $mod['name'] }}</span>
                    <span class="badge bg-success ms-auto">Ativo</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Atividade recente</div>
            <ul class="list-group list-group-flush">
                @forelse($recentActivity as $log)
                <li class="list-group-item" style="font-size:.8rem">
                    <code>{{ $log->action }}</code>
                    <span class="text-muted ms-1">por {{ $log->user?->name ?? '—' }}</span>
                    <span class="float-end text-muted">{{ $log->timestamp->diffForHumans() }}</span>
                </li>
                @empty
                <li class="list-group-item text-muted text-center py-3">Nenhuma atividade recente.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection