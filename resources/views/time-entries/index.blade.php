@extends('layouts.app')
@section('title', 'Registros de Ponto')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i>Registros de Ponto</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('time-entries.monthly-report') }}" class="btn btn-outline-secondary btn-sm">Relatório Mensal</a>
        <a href="{{ route('time-entries.corrections') }}" class="btn btn-outline-secondary btn-sm">Correções</a>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body pb-0">
        <form method="GET" action="{{ route('time-entries.index') }}">
            <div class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Unidade</label>
                    <select name="unit_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ $unitId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Funcionário</label>
                    <select name="user_id" class="form-select form-select-sm" style="width:180px">
                        <option value="">Todos</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">De</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" style="width:140px">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Até</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" style="width:140px">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-secondary align-self-end">Filtrar</button>
            </div>
        </form>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Funcionário</th><th>Unidade</th><th>Tipo</th><th>Registrado em</th></tr>
        </thead>
        <tbody>
            @forelse($entries as $e)
            <tr>
                <td>{{ $e->user?->name ?? '—' }}</td>
                <td>{{ $e->unit?->name ?? '—' }}</td>
                <td>
                    <span class="badge bg-{{ $e->type === 'clock_in' ? 'success' : ($e->type === 'clock_out' ? 'info' : 'warning text-dark') }}">
                        {{ ['clock_in'=>'Entrada','clock_out'=>'Saída','correction'=>'Correção'][$e->type] ?? $e->type }}
                    </span>
                </td>
                <td>{{ $e->recorded_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-muted text-center py-3">Nenhum registro encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($entries->hasPages())
    <div class="card-footer bg-white">{{ $entries->links() }}</div>
    @endif
</div>
@endsection