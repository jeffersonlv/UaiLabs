@extends('layouts.app')
@section('title', 'Meu Ponto')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-clock me-2"></i>Meu Ponto — {{ $user->name }}</h4>
    <a href="{{ url('/clock') }}" class="btn btn-primary btn-sm"><i class="bi bi-clock me-1"></i>Bater Ponto</a>
</div>
<form method="GET" action="{{ route('time-entries.dashboard') }}" class="mb-3">
    <div class="d-flex gap-2 align-items-end">
        <div>
            <label class="form-label form-label-sm mb-1 text-muted">Mês</label>
            <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" onchange="this.form.submit()">
        </div>
    </div>
</form>

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    @php
        $wMin = $calc['worked_minutes'];
        $sMin = $calc['scheduled_minutes'];
        $oMin = $calc['overtime_minutes'];
    @endphp
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Trabalhado</div>
            <div class="fw-bold fs-4 text-primary">{{ \App\Services\TimeCalculationService::formatMinutes($wMin) }}</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Programado</div>
            <div class="fw-bold fs-4 text-secondary">{{ \App\Services\TimeCalculationService::formatMinutes($sMin) }}</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Horas extras</div>
            <div class="fw-bold fs-4 {{ $oMin > 0 ? 'text-warning' : 'text-success' }}">{{ \App\Services\TimeCalculationService::formatMinutes($oMin) }}</div>
        </div>
    </div>
</div>

{{-- Daily breakdown --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Detalhamento diário — {{ \Carbon\Carbon::parse($month)->format('F Y') }}</div>
    <table class="table table-sm mb-0">
        <thead class="table-light">
            <tr><th>Data</th><th>Trabalhado</th><th>Programado</th><th>Extra</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($calc['breakdown'] as $date => $day)
            @php
                $d = \Carbon\Carbon::parse($date);
                $isToday = $d->isToday();
            @endphp
            <tr class="{{ $isToday ? 'table-primary' : '' }}">
                <td>{{ $d->format('d/m D') }}</td>
                <td>{{ \App\Services\TimeCalculationService::formatMinutes($day['worked_minutes']) }}</td>
                <td class="text-muted">{{ \App\Services\TimeCalculationService::formatMinutes($day['scheduled_minutes']) }}</td>
                <td class="{{ $day['overtime_minutes'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">
                    {{ $day['overtime_minutes'] > 0 ? '+' . \App\Services\TimeCalculationService::formatMinutes($day['overtime_minutes']) : '—' }}
                </td>
                <td>
                    @if($day['has_open_pair'])
                        <span class="badge bg-warning text-dark">Ponto aberto</span>
                    @elseif($day['worked_minutes'] === 0 && $day['scheduled_minutes'] > 0 && $d->isPast() && !$d->isToday())
                        <span class="badge bg-danger">Faltante</span>
                    @elseif($day['worked_minutes'] > 0)
                        <span class="badge bg-success">OK</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection