@extends('layouts.app')
@section('title', 'Quadro de Alocação')
@section('content')

@php
$dayLabels = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
$today     = \Carbon\Carbon::today()->toDateString();
$prevWeek  = \Carbon\Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek()->subWeek()->format('o-\WW');
$nextWeek  = \Carbon\Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek()->addWeek()->format('o-\WW');
$periods   = ['manha' => ['label'=>'Manhã', 'icon'=>'bi-sunrise',    'hint'=>'até 12h'],
              'tarde'  => ['label'=>'Tarde', 'icon'=>'bi-sun',        'hint'=>'12h–18h'],
              'noite'  => ['label'=>'Noite', 'icon'=>'bi-moon-stars', 'hint'=>'18h+']];
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Quadro de Alocação</h4>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('shifts.timesheet', array_filter(['week'=>$weekParam,'unit_id'=>$unitId])) }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-table me-1"></i>Planilha
        </a>
        <a href="{{ route('shifts.calendar', array_filter(['unit_id'=>$unitId])) }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-calendar-month me-1"></i>Calendário
        </a>
        @if($isManager)
        <a href="{{ route('stations.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-geo-alt me-1"></i>Estações
        </a>
        @endif
    </div>
</div>

{{-- Filtros --}}
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <a href="{{ route('shifts.board', array_filter(['week'=>$prevWeek,'unit_id'=>$unitId])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
    <span class="fw-semibold small">
        {{ $weekStart->format('d/m') }} – {{ $weekEnd->format('d/m/Y') }}
    </span>
    <a href="{{ route('shifts.board', array_filter(['week'=>$nextWeek,'unit_id'=>$unitId])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
    <a href="{{ route('shifts.board', array_filter(['unit_id'=>$unitId])) }}"
       class="btn btn-sm btn-outline-primary">Hoje</a>

    @if($units->isNotEmpty())
    <form method="GET" action="{{ route('shifts.board') }}" class="d-flex gap-1 ms-2">
        <input type="hidden" name="week" value="{{ $weekParam }}">
        <select name="unit_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
            <option value="">Todas as unidades</option>
            @foreach($units as $u)
            <option value="{{ $u->id }}" {{ $unitId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </form>
    @endif

    <span class="ms-auto small text-muted d-flex align-items-center gap-1">
        <span class="spinner-grow spinner-grow-sm text-success" id="pollSpinner" style="display:none!important"></span>
        <span id="lastUpdate">—</span>
    </span>
</div>

@if($stations->isEmpty())
<div class="alert alert-info">
    Nenhuma estação cadastrada. <a href="{{ route('stations.index') }}">Cadastre estações</a> para usar o quadro.
</div>
@else

@foreach($periods as $periodKey => $period)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <i class="bi {{ $period['icon'] }} text-primary"></i>
        <span class="fw-semibold">{{ $period['label'] }}</span>
        <small class="text-muted">({{ $period['hint'] }})</small>
    </div>
    <div class="card-body p-0" style="overflow-x:auto">
        <table class="table table-bordered table-sm mb-0" style="table-layout:fixed;width:100%;min-width:700px">
            <thead class="table-light">
                <tr>
                    <th style="width:130px">Estação</th>
                    @foreach($days as $i => $day)
                    <th class="text-center {{ $day->toDateString() === $today ? 'table-primary' : '' }}"
                        style="min-width:115px">
                        {{ $dayLabels[$i] }}<br>
                        <small class="fw-normal opacity-75">{{ $day->format('d/m') }}</small>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($stations as $station)
                <tr>
                    <td class="align-middle fw-semibold small">
                        <span class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-block flex-shrink-0"
                                  style="width:10px;height:10px;background:{{ $station->color }}"></span>
                            {{ $station->name }}
                        </span>
                    </td>
                    @foreach($days as $day)
                    <td class="{{ $day->toDateString() === $today ? 'table-primary bg-opacity-25' : '' }} p-1 align-top board-drop-cell"
                        data-period="{{ $periodKey }}"
                        data-station="{{ $station->id }}"
                        data-date="{{ $day->toDateString() }}"
                        style="min-height:50px">
                        <div class="cell-content d-flex flex-column gap-1">
                            <span class="text-muted small fst-italic empty-label">—</span>
                        </div>
                    </td>
                    @endforeach
                </tr>
                @endforeach
                {{-- Linha: Não designados --}}
                <tr>
                    <td class="align-middle fw-semibold small text-secondary">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-question-circle text-secondary"></i>
                            Não designados
                        </span>
                    </td>
                    @foreach($days as $day)
                    <td class="{{ $day->toDateString() === $today ? 'table-warning bg-opacity-25' : 'bg-light' }} p-1 align-top board-drop-cell"
                        data-period="{{ $periodKey }}"
                        data-unassigned="1"
                        data-date="{{ $day->toDateString() }}"
                        style="min-height:50px">
                        <div class="cell-content d-flex flex-column gap-1">
                            <span class="text-muted small fst-italic empty-label">—</span>
                        </div>
                    </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endif

<script>
const IS_MANAGER = {{ $isManager ? 'true' : 'false' }};
const BOARD_URL  = '{{ route("shifts.board-data") }}?week={{ $weekParam }}&unit_id={{ $unitId ?? "" }}';
const CSRF       = document.querySelector('meta[name=csrf-token]').content;
const POLL_MS    = 15000;
let   draggingId = null;

function renderBadge(emp) {
    const b = document.createElement('span');
    b.className       = 'badge bg-light text-dark border small d-block text-start text-truncate board-badge';
    b.style.maxWidth  = '100%';
    b.style.fontSize  = '.65rem';
    b.style.cursor    = IS_MANAGER ? 'grab' : 'default';
    b.title           = emp.name;
    b.textContent     = emp.name;
    b.dataset.shiftId = emp.shift_id;
    if (IS_MANAGER) {
        b.draggable = true;
        b.addEventListener('dragstart', function (e) {
            draggingId = this.dataset.shiftId;
            e.dataTransfer.effectAllowed = 'move';
        });
        b.addEventListener('dragend', function () {
            draggingId = null;
        });
    }
    return b;
}

async function loadBoard() {
    const spinner = document.getElementById('pollSpinner');
    const lastUpd = document.getElementById('lastUpdate');
    if (spinner) spinner.style.removeProperty('display');

    try {
        const res  = await fetch(BOARD_URL);
        const data = await res.json();

        document.querySelectorAll('td[data-period]').forEach(td => {
            td.querySelector('.cell-content').innerHTML =
                '<span class="text-muted small fst-italic empty-label">—</span>';
        });

        for (const [period, periodData] of Object.entries(data)) {
            // Designated
            const assigned = periodData.assigned || {};
            for (const [stationId, dates] of Object.entries(assigned)) {
                for (const [date, employees] of Object.entries(dates)) {
                    const td = document.querySelector(
                        `td[data-period="${period}"][data-station="${stationId}"][data-date="${date}"]`
                    );
                    if (!td) continue;
                    const container = td.querySelector('.cell-content');
                    container.innerHTML = '';
                    employees.forEach(emp => container.appendChild(renderBadge(emp)));
                }
            }
            // Unassigned
            const unassigned = periodData.unassigned || {};
            for (const [date, employees] of Object.entries(unassigned)) {
                const td = document.querySelector(
                    `td[data-period="${period}"][data-unassigned="1"][data-date="${date}"]`
                );
                if (!td) continue;
                const container = td.querySelector('.cell-content');
                container.innerHTML = '';
                employees.forEach(emp => container.appendChild(renderBadge(emp)));
            }
        }

        const now = new Date();
        if (lastUpd) lastUpd.textContent = 'Atualizado '
            + now.getHours().toString().padStart(2, '0') + ':'
            + now.getMinutes().toString().padStart(2, '0') + ':'
            + now.getSeconds().toString().padStart(2, '0');
    } catch (e) {
        const lastUpd = document.getElementById('lastUpdate');
        if (lastUpd) lastUpd.textContent = 'Erro ao atualizar';
    } finally {
        const spinner = document.getElementById('pollSpinner');
        if (spinner) spinner.style.display = 'none';
    }
}

// Drag-and-drop (managers only)
if (IS_MANAGER) {
    document.addEventListener('dragover', function (e) {
        if (e.target.closest('.board-drop-cell')) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        }
    });

    document.addEventListener('dragenter', function (e) {
        const cell = e.target.closest('.board-drop-cell');
        if (cell) cell.classList.add('drop-hover');
    });

    document.addEventListener('dragleave', function (e) {
        const cell = e.target.closest('.board-drop-cell');
        if (cell && !cell.contains(e.relatedTarget)) cell.classList.remove('drop-hover');
    });

    document.addEventListener('drop', async function (e) {
        const cell = e.target.closest('.board-drop-cell');
        if (!cell || !draggingId) return;
        e.preventDefault();
        cell.classList.remove('drop-hover');

        const stationId = cell.dataset.unassigned ? '' : (cell.dataset.station || '');
        const shiftId   = draggingId;
        draggingId      = null;

        const body = new FormData();
        body.append('_token',     CSRF);
        body.append('station_id', stationId);

        try {
            const res = await fetch('/shifts/' + shiftId + '/assign-station', { method: 'PATCH', body });
            if (res.ok) loadBoard();
        } catch (err) {
            console.error('Assign station failed', err);
        }
    });
}

loadBoard();
setInterval(loadBoard, POLL_MS);
</script>

@push('styles')
<style>
.board-drop-cell.drop-hover {
    background: #cfe2ff !important;
    outline: 2px dashed #0d6efd;
}
.board-badge[draggable="true"]:active { opacity: .6; }
</style>
@endpush

@endsection
