@extends('layouts.app')
@section('title', 'Escala — Calendário')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-calendar-month me-2"></i>Escala — Calendário</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('shifts.timesheet', array_filter(['unit_id'=>$unitId])) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-table me-1"></i>Planilha
        </a>
        @if(auth()->user()->isManagerOrAbove())
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#shiftModal">
            <i class="bi bi-plus-lg me-1"></i>Turno
        </button>
        @endif
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

{{-- ── Quadro de Alocação ────────────────────────────────────────────────────── --}}
@php
$boardDayLabels = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
$boardPeriods   = ['manha' => ['label'=>'Manhã','icon'=>'bi-sunrise','hint'=>'até 12h'],
                   'tarde' => ['label'=>'Tarde','icon'=>'bi-sun',    'hint'=>'12h–18h'],
                   'noite' => ['label'=>'Noite','icon'=>'bi-moon-stars','hint'=>'18h+']];
$boardPrev = \Carbon\Carbon::now()->setISODate(...explode('-W',$boardWeek))->startOfWeek()->subWeek()->format('o-\WW');
$boardNext = \Carbon\Carbon::now()->setISODate(...explode('-W',$boardWeek))->startOfWeek()->addWeek()->format('o-\WW');
$boardToday = \Carbon\Carbon::today()->toDateString();
@endphp

<div class="d-flex align-items-center gap-2 mt-4 mb-2">
    <h5 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Quadro de Alocação</h5>
    {{-- Navegação de semana do quadro --}}
    <a href="{{ route('shifts.calendar', array_filter(['unit_id'=>$unitId,'month'=>$month,'board_week'=>$boardPrev])) }}"
       class="btn btn-sm btn-outline-secondary ms-2"><i class="bi bi-chevron-left"></i></a>
    <span class="small fw-semibold">{{ $boardStart->format('d/m') }} – {{ $boardEnd->format('d/m/Y') }}</span>
    <a href="{{ route('shifts.calendar', array_filter(['unit_id'=>$unitId,'month'=>$month,'board_week'=>$boardNext])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
    <span class="ms-auto small text-muted" id="boardLastUpdate"></span>
</div>

@if($stations->isEmpty())
<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    Nenhuma estação cadastrada.
    @if(auth()->user()->isManagerOrAbove())
        <a href="{{ route('stations.index') }}">Cadastre estações</a> para usar o quadro.
    @endif
</div>
@else

@foreach($boardPeriods as $periodKey => $period)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <i class="bi {{ $period['icon'] }} text-primary"></i>
        <span class="fw-semibold small">{{ $period['label'] }}</span>
        <small class="text-muted">({{ $period['hint'] }})</small>
    </div>
    <div class="card-body p-0" style="overflow-x:auto">
        <table class="table table-bordered table-sm mb-0" style="min-width:700px">
            <thead class="table-light">
                <tr>
                    <th style="width:110px" class="small">Estação</th>
                    @foreach($boardDays as $i => $bday)
                    <th class="text-center small {{ $bday->toDateString() === $boardToday ? 'table-primary' : '' }}">
                        {{ $boardDayLabels[$i] }}<br>
                        <span class="fw-normal opacity-75" style="font-size:.7rem">{{ $bday->format('d/m') }}</span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($stations as $station)
                <tr>
                    <td class="align-middle small fw-semibold">
                        <span class="d-flex align-items-center gap-1">
                            <span class="rounded-circle flex-shrink-0"
                                  style="width:9px;height:9px;display:inline-block;background:{{ $station->color }}"></span>
                            {{ $station->name }}
                        </span>
                    </td>
                    @foreach($boardDays as $bday)
                    <td class="p-1 align-top {{ $bday->toDateString() === $boardToday ? 'table-primary bg-opacity-25' : '' }}"
                        data-period="{{ $periodKey }}"
                        data-station="{{ $station->id }}"
                        data-date="{{ $bday->toDateString() }}">
                        <div class="board-cell d-flex flex-column gap-1">
                            <span class="text-muted board-empty" style="font-size:.7rem">—</span>
                        </div>
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endif

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

@if(auth()->user()->isManagerOrAbove())
{{-- Modal: Novo Turno --}}
<div class="modal fade" id="shiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Turno</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="shiftForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Funcionário</label>
                        <select name="user_id" class="form-select" required>
                            @foreach($unitUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Início</label>
                            <input type="datetime-local" name="start_at" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Fim</label>
                            <input type="datetime-local" name="end_at" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="type" class="form-select">
                            @foreach(\App\Models\Shift::TYPES as $key => $info)
                                <option value="{{ $key }}">{{ $info['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                    <input type="hidden" name="unit_id" value="{{ $unitId }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// ── Quadro de alocação — polling ─────────────────────────────────────────────
(function () {
    var BOARD_URL = '{{ route("shifts.board-data") }}?week={{ $boardWeek }}&unit_id={{ $unitId ?? "" }}';
    var POLL_MS   = 15000;
    var lastUpd   = document.getElementById('boardLastUpdate');

    function loadBoard() {
        fetch(BOARD_URL)
            .then(function(r){ return r.json(); })
            .then(function(data) {
                document.querySelectorAll('td[data-period]').forEach(function(td) {
                    td.querySelector('.board-cell').innerHTML =
                        '<span class="text-muted board-empty" style="font-size:.7rem">—</span>';
                });

                Object.entries(data).forEach(function([period, periodData]) {
                    var assigned = periodData.assigned || {};
                    Object.entries(assigned).forEach(function([stationId, dates]) {
                        Object.entries(dates).forEach(function([date, emps]) {
                            var td = document.querySelector(
                                'td[data-period="'+period+'"][data-station="'+stationId+'"][data-date="'+date+'"]'
                            );
                            if (!td) return;
                            var cell = td.querySelector('.board-cell');
                            cell.innerHTML = '';
                            emps.forEach(function(emp) {
                                var b = document.createElement('span');
                                b.className   = 'badge bg-light text-dark border d-block text-start text-truncate';
                                b.style.maxWidth = '100%';
                                b.style.fontSize = '.65rem';
                                b.title       = emp.name;
                                b.textContent = emp.name;
                                cell.appendChild(b);
                            });
                        });
                    });
                });

                if (lastUpd) {
                    var n = new Date();
                    lastUpd.textContent = 'Atualizado '
                        + String(n.getHours()).padStart(2,'0') + ':'
                        + String(n.getMinutes()).padStart(2,'0') + ':'
                        + String(n.getSeconds()).padStart(2,'0');
                }
            })
            .catch(function(){ if (lastUpd) lastUpd.textContent = 'Erro ao atualizar'; });
    }

    @if(!$stations->isEmpty())
    loadBoard();
    setInterval(loadBoard, POLL_MS);
    @endif
})();

// ── Calendário — clique nos turnos ──────────────────────────────────────────
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

    @if(auth()->user()->isManagerOrAbove())
    var shiftForm = document.getElementById('shiftForm');
    if (shiftForm) {
        shiftForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(this);
            var body = {};
            fd.forEach(function (v, k) { body[k] = v; });
            fetch('{{ route("shifts.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body)
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('shiftModal')).hide();
                    window.location.reload();
                }
            });
        });
    }
    @endif
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
