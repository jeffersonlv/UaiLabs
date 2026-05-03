@extends('layouts.app')
@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Log de Atividades</h4>
    <span class="text-muted small">{{ $logs->total() }} {{ $logs->total() === 1 ? 'registro' : 'registros' }}</span>
</div>

{{-- ── Filtros ─────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('audit-log.index') }}" class="row g-2 align-items-end">

            @if(auth()->user()->isSuperAdmin())
            <div class="col-12 col-md-auto">
                <label class="form-label form-label-sm mb-1 text-muted">Empresa</label>
                <select name="company_id" class="form-select form-select-sm" style="min-width:180px" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" {{ $selectedCompany == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-12 col-md-auto">
                <label class="form-label form-label-sm mb-1 text-muted">Colaborador</label>
                <select name="user_id" class="form-select form-select-sm" style="min-width:160px">
                    <option value="">Todos</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ $selectedUser == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-auto">
                <label class="form-label form-label-sm mb-1 text-muted">Ação</label>
                <select name="action" class="form-select form-select-sm" style="min-width:160px">
                    <option value="">Todas</option>
                    @foreach($actions as $key => $label)
                        <option value="{{ $key }}" {{ $selectedAction === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-auto">
                <label class="form-label form-label-sm mb-1 text-muted">De</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                    value="{{ $dateFrom->toDateString() }}">
            </div>

            <div class="col-auto">
                <label class="form-label form-label-sm mb-1 text-muted">Até</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                    value="{{ $dateTo->toDateString() }}">
            </div>

            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
                <a href="{{ route('audit-log.index') }}" class="btn btn-sm btn-outline-secondary">Hoje</a>
            </div>
        </form>
    </div>
</div>

{{-- ── Tabela ──────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:145px">Data / Hora</th>
                    <th>Colaborador</th>
                    <th style="width:155px">Ação</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-muted small">
                        {{ $log->timestamp->format('d/m/Y') }}<br>
                        <span class="fw-semibold text-dark">{{ $log->timestamp->format('H:i:s') }}</span>
                    </td>
                    <td>
                        @if($log->user)
                            <div class="fw-semibold">{{ $log->user->name }}</div>
                            <div class="text-muted" style="font-size:.75rem">
                                <span class="badge
                                    {{ $log->user->role === 'admin'   ? 'bg-danger'
                                    : ($log->user->role === 'manager' ? 'bg-warning text-dark'
                                    : 'bg-secondary') }}">
                                    {{ ucfirst($log->user->role) }}
                                </span>
                            </div>
                        @else
                            <span class="text-muted fst-italic">—</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $badgeMap = [
                                'task.complete' => ['bg-success',   'Concluiu tarefa'],
                                'task.reopen'   => ['bg-warning text-dark', 'Reexecutou tarefa'],
                                'login'         => ['bg-primary',   'Login'],
                                'logout'        => ['bg-secondary', 'Logout'],
                            ];
                            [$badge, $label] = $badgeMap[$log->action] ?? ['bg-light text-dark', $log->action];
                        @endphp
                        <span class="badge {{ $badge }}">{{ $label }}</span>
                    </td>
                    <td class="small text-muted">
                        @if($log->details)
                            @if(isset($log->details['atividade']))
                                <span class="text-dark">{{ $log->details['atividade'] }}</span>
                                @if(isset($log->details['periodo']))
                                    <span class="ms-1">({{ \Carbon\Carbon::parse($log->details['periodo'])->format('d/m/Y') }})</span>
                                @endif
                                @if(isset($log->details['justificativa']))
                                    <br><span class="fst-italic">Justif.: {{ $log->details['justificativa'] }}</span>
                                @endif
                            @elseif(isset($log->details['ip']))
                                IP: {{ $log->details['ip'] }}
                            @else
                                {{ implode(' · ', array_map(fn($k,$v) => "$k: $v", array_keys($log->details), $log->details)) }}
                            @endif
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">Nenhum registro encontrado para os filtros selecionados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Paginação ───────────────────────────────────────────── --}}
@if($logs->hasPages())
<div class="mt-3 d-flex justify-content-center">
    {{ $logs->links() }}
</div>
@endif

@endsection
