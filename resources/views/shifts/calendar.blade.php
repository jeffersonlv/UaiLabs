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
    $firstDow    = (int) $start->dayOfWeek; // 0=Sun
    $byDay = $shifts->groupBy(fn($s) => \Illuminate\Support\Carbon::parse($s->start_at)->day);
    $typeColors = ['work'=>'primary','vacation'=>'success','leave'=>'warning','holiday'=>'secondary'];
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-body p-3">
        <div class="row g-0 text-center fw-semibold text-muted mb-1" style="font-size:.8rem">
            @foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $d)
                <div class="col">{{ $d }}</div>
            @endforeach
        </div>
        <div class="row g-1" id="cal-grid">
            @for($i = 0; $i < $firstDow; $i++)
                <div class="col" style="min-height:80px"></div>
            @endfor
            @for($d = 1; $d <= $daysInMonth; $d++)
                @php $dayShifts = $byDay->get($d, collect()); @endphp
                <div class="col border rounded p-1" style="min-height:80px;font-size:.75rem">
                    <div class="fw-semibold mb-1">{{ $d }}</div>
                    @foreach($dayShifts->take(3) as $s)
                        <div class="badge bg-{{ $typeColors[$s->type] ?? 'secondary' }} d-block text-truncate mb-1"
                             style="font-size:.6rem;cursor:pointer" onclick="location.href='/shifts/{{ $s->id }}'">
                            {{ $s->user->name ?? '—' }}
                            {{ \Carbon\Carbon::parse($s->start_at)->format('H:i') }}
                        </div>
                    @endforeach
                    @if($dayShifts->count() > 3)
                        <div class="text-muted" style="font-size:.6rem">+{{ $dayShifts->count() - 3 }} mais</div>
                    @endif
                </div>
                @if(($d + $firstDow) % 7 === 0 && $d < $daysInMonth)
                    </div><div class="row g-1">
                @endif
            @endfor
        </div>
    </div>
</div>
@endsection