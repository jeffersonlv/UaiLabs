@extends('layouts.app')
@section('title', 'Relatório Mensal de Ponto')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Relatório Mensal — {{ \Carbon\Carbon::parse($month)->format('F Y') }}</h4>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Imprimir</button>
</div>
<form method="GET" action="{{ route('time-entries.monthly-report') }}" class="d-flex gap-2 mb-3">
    <select name="unit_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
        <option value="">Todas as unidades</option>
        @foreach($units as $u)
            <option value="{{ $u->id }}" {{ $unitId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
        @endforeach
    </select>
    <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" style="width:160px" onchange="this.form.submit()">
</form>

<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Funcionário</th>
                <th>Turno</th>
                <th>Trabalhado</th>
                <th>Programado</th>
                <th>Regular</th>
                <th>Extra</th>
                <th>Dias OK</th>
                <th>Faltantes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report as $row)
            @php
                $totals = $row['totals'];
                $u      = $row['user'];
                $daysOk = collect($totals['breakdown'])->filter(fn($d) => $d['worked_minutes'] > 0)->count();
                $daysMissing = collect($totals['breakdown'])->filter(fn($d) => $d['worked_minutes'] === 0 && $d['scheduled_minutes'] > 0)->count();
                $overUnder = $totals['worked_minutes'] - $totals['scheduled_minutes'];
            @endphp
            <tr>
                <td class="fw-medium">{{ $u->name }}</td>
                <td class="text-muted small">{{ $u->workSchedule?->name ?? '—' }}</td>
                <td>{{ \App\Services\TimeCalculationService::formatMinutes($totals['worked_minutes']) }}</td>
                <td class="text-muted">{{ \App\Services\TimeCalculationService::formatMinutes($totals['scheduled_minutes']) }}</td>
                <td>{{ \App\Services\TimeCalculationService::formatMinutes($totals['regular_minutes']) }}</td>
                <td class="{{ $totals['overtime_minutes'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">
                    {{ $totals['overtime_minutes'] > 0 ? '+' . \App\Services\TimeCalculationService::formatMinutes($totals['overtime_minutes']) : '—' }}
                </td>
                <td>{{ $daysOk }}</td>
                <td class="{{ $daysMissing > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $daysMissing ?: '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-muted text-center py-3">Nenhum funcionário no período.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection