@extends('layouts.app')
@section('title', 'Escala — Calendário')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-calendar-month me-2"></i>Escala — Calendário</h4>
    <a href="{{ route('shifts.index', ['unit_id'=>$unitId]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-bar-chart-steps me-1"></i>Timeline
    </a>
</div>
<form method="GET" action="{{ route('shifts.calendar') }}" class="d-flex gap-2 mb-3">
    <select name="unit_id" class="form-select form-select-sm" style="width:200px" onchange="this.form.submit()">
        @foreach($units as $u)
            <option value="{{ $u->id }}" {{ $unitId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
        @endforeach
    </select>
    <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" style="width:160px" onchange="this.form.submit()">
</form>

@php
    $daysInMonth = $start->daysInMonth;
    $firstDow    = (int) $start->dayOfWeek; // 0=Dom
    $byDay       = $shifts->groupBy(fn($s) => \Carbon\Carbon::parse($s->start_at)->day);
    $typeColors  = ['work'=>'primary','vacation'=>'success','leave'=>'warning','holiday'=>'secondary'];
    $totalCells  = $firstDow + $daysInMonth;
    $totalCells  += (7 - $totalCells % 7) % 7; // pad to full weeks
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-body p-3">
        {{-- Header dias da semana --}}
        <div class="cal-grid mb-1">
            @foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $d)
            <div class="cal-header">{{ $d }}</div>
            @endforeach
        </div>

        {{-- Grid de dias --}}
        <div class="cal-grid">
            @for($i = 0; $i < $totalCells; $i++)
            @php $day = $i - $firstDow + 1; @endphp
            @if($day < 1 || $day > $daysInMonth)
                <div class="cal-cell cal-empty"></div>
            @else
                @php $dayShifts = $byDay->get($day, collect()); @endphp
                <div class="cal-cell">
                    <div class="cal-day-num">{{ $day }}</div>
                    @foreach($dayShifts->take(3) as $s)
                    <a href="{{ route('shifts.show', $s) }}"
                       class="badge bg-{{ $typeColors[$s->type] ?? 'secondary' }} d-block text-truncate mb-1 cal-badge">
                        {{ $s->user->name ?? '—' }} {{ \Carbon\Carbon::parse($s->start_at)->format('H:i') }}
                    </a>
                    @endforeach
                    @if($dayShifts->count() > 3)
                    <div class="cal-more">+{{ $dayShifts->count() - 3 }} mais</div>
                    @endif
                </div>
            @endif
            @endfor
        </div>
    </div>
</div>

@push('styles')
<style>
.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 3px;
}
.cal-header {
    text-align: center;
    font-size: .75rem;
    font-weight: 600;
    color: #6c757d;
    padding: 2px 0;
}
.cal-cell {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 4px;
    min-height: 72px;
    font-size: .72rem;
    overflow: hidden;
}
.cal-empty {
    border-color: transparent;
    background: transparent;
}
.cal-day-num {
    font-weight: 600;
    margin-bottom: 3px;
    font-size: .75rem;
}
.cal-badge {
    font-size: .6rem;
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cal-more {
    font-size: .6rem;
    color: #6c757d;
}
@media (max-width: 480px) {
    .cal-cell { min-height: 52px; padding: 2px; }
    .cal-day-num { font-size: .65rem; }
}
</style>
@endpush
@endsection
