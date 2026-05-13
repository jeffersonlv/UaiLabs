@extends('layouts.app')
@section('title', 'Escala — Calendário')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-calendar-month me-2"></i>Escala — Calendário</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('shifts.timesheet', array_filter(['unit_id'=>$unitId])) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-table me-1"></i>Planilha
        </a>
        <a href="{{ route('shifts.board', array_filter(['unit_id'=>$unitId])) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-grid-3x3-gap me-1"></i>Quadro
        </a>
        <a href="{{ route('shifts.index', ['unit_id'=>$unitId]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bar-chart-steps me-1"></i>Timeline
        </a>
    </div>
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
                    <div class="cal-badge bg-{{ $typeColors[$s->type] ?? 'secondary' }} text-white mb-1"
                         data-id="{{ $s->id }}"
                         data-name="{{ $s->user->name ?? '—' }}"
                         data-date="{{ $s->start_at->format('d/m/Y') }}"
                         data-start="{{ $s->start_at->format('H:i') }}"
                         data-end="{{ $s->end_at->format('H:i') }}"
                         data-type="{{ $s->typeLabel() }}"
                         data-color="{{ $typeColors[$s->type] ?? 'secondary' }}"
                         data-notes="{{ $s->notes ?? '' }}"
                         data-edit="{{ route('shifts.show', $s) }}"
                         data-delete="{{ route('shifts.destroy', $s) }}">
                        {{ $s->user->name ?? '—' }} · {{ $s->start_at->format('H:i') }}–{{ $s->end_at->format('H:i') }}
                    </div>
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

{{-- Trigger hidden para data-API do Bootstrap (não depende de window.bootstrap) --}}
<button id="sdmTrigger" type="button" class="d-none"
        data-bs-toggle="modal" data-bs-target="#shiftDetailModal"></button>

{{-- Modal: Detalhe do turno --}}
<div class="modal fade" id="shiftDetailModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="sdmName"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">
                <div class="small fw-semibold mb-1" id="sdmTime"></div>
                <span id="sdmType" class="badge mb-2"></span>
                <div class="text-muted small" id="sdmNotes"></div>
            </div>
            <div class="modal-footer py-2 d-flex flex-column gap-2 align-items-stretch">
                @if(auth()->user()->isManagerOrAbove())
                <a id="sdmEdit" href="#" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-pencil me-1"></i>Editar
                </a>
                <button id="sdmDelete" type="button" class="btn btn-sm btn-outline-danger w-100">
                    <i class="bi bi-trash me-1"></i>Excluir turno
                </button>
                @endif
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrf    = document.querySelector('meta[name=csrf-token]').content;
    var modalEl = document.getElementById('shiftDetailModal');

    document.querySelectorAll('.cal-badge').forEach(function (badge) {
        badge.addEventListener('click', function () {
            document.getElementById('sdmName').textContent  = badge.dataset.name;
            document.getElementById('sdmTime').textContent  = badge.dataset.date + ' · ' + badge.dataset.start + '–' + badge.dataset.end;
            document.getElementById('sdmType').className    = 'badge bg-' + badge.dataset.color;
            document.getElementById('sdmType').textContent  = badge.dataset.type;
            document.getElementById('sdmNotes').textContent = badge.dataset.notes || '';
            document.getElementById('sdmEdit').href         = badge.dataset.edit;
            document.getElementById('sdmDelete').dataset.url  = badge.dataset.delete;
            document.getElementById('sdmDelete').dataset.name = badge.dataset.name;
            document.getElementById('sdmTrigger').click();
        });
    });

    document.getElementById('sdmDelete').addEventListener('click', function () {
        if (!confirm('Excluir turno de ' + this.dataset.name + '?')) return;
        fetch(this.dataset.url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d.ok) window.location.reload(); });
    });
});
</script>
@endpush

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
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border-radius: 3px;
    padding: 1px 4px;
    cursor: pointer;
    user-select: none;
}
.cal-badge:hover { opacity: .85; }
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
