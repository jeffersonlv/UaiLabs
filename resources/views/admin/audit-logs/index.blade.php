@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Log de Atividades</h4>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body pb-0">
        <form method="GET" action="{{ request()->url() }}">
            <div class="d-flex flex-wrap gap-2 align-items-end">
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Empresa</label>
                    <select name="company_id" class="form-select form-select-sm" style="width:200px" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach($companies ?? [] as $c)
                            <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Ação</label>
                    <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach($actions ?? [] as $a)
                            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Usuário</label>
                    <select name="user_id" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach($users ?? [] as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-1">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" style="width:140px">
                    <input type="date" name="date_to"   value="{{ request('date_to')   }}" class="form-control form-control-sm" style="width:140px">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-secondary align-self-end">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <table class="table table-hover table-sm mb-0">
        <thead class="table-light">
            <tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Entidade</th><th>Detalhes</th></tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td class="text-muted" style="font-size:.8rem">{{ $log->timestamp?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>{{ $log->user?->name ?? '—' }}</td>
                <td><code style="font-size:.75rem">{{ $log->action }}</code></td>
                <td class="text-muted small">{{ $log->entity }}{{ $log->entity_id ? ' #'.$log->entity_id : '' }}</td>
                <td class="text-muted" style="font-size:.75rem">
                    @if($log->details)
                        @foreach($log->details as $k => $v)
                            <span class="me-2"><strong>{{ $k }}</strong>: {{ is_array($v) ? json_encode($v) : $v }}</span>
                        @endforeach
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted text-center py-3">Nenhum log encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($logs->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between">
        <small class="text-muted">{{ $logs->firstItem() }}–{{ $logs->lastItem() }} de {{ $logs->total() }}</small>
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection