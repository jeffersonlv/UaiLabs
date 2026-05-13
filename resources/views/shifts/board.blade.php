@extends('layouts.app')
@section('title', 'Quadro de Alocação')
@section('content')

@php
$dayLabels = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
$today     = \Carbon\Carbon::today()->toDateString();
$prevWeek  = \Carbon\Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek()->subWeek()->format('o-\WW');
$nextWeek  = \Carbon\Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek()->addWeek()->format('o-\WW');
$periods   = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'];
$periodIcons = ['manha' => 'bi-sunrise', 'tarde' => 'bi-sun', 'noite' => 'bi-moon-stars'];
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
    </div>
</div>

{{-- Filtros --}}
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <a href="{{ route('shifts.board', array_filter(['week'=>$prevWeek,'unit_id'=>$unitId])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
    <span class="fw-semibold small">
        {{ $weekStart->format('d/m') }} – {{ $weekStart->copy()->endOfWeek()->format('d/m/Y') }}
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

    {{-- Indicador de atualização automática --}}
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

{{-- Um card por período --}}
@foreach($periods as $periodKey => $periodLabel)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <i class="bi {{ $periodIcons[$periodKey] }} text-primary"></i>
        <span class="fw-semibold">{{ $periodLabel }}</span>
        <small class="text-muted">
            @if($periodKey === 'manha') (até 12h) @elseif($periodKey === 'tarde') (12h–18h) @else (18h+) @endif
        </small>
    </div>
    <div class="card-body p-0" style="overflow-x:auto">
        <table class="table table-bordered table-sm mb-0" style="min-width:900px">
            <thead class="table-light">
                <tr>
                    <th style="width:120px">Estação</th>
                    @foreach($days as $i => $day)
                    <th class="text-center {{ $day->toDateString() === $today ? 'table-primary' : '' }}"
                        style="min-width:110px">
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
                    <td class="{{ $day->toDateString() === $today ? 'table-primary bg-opacity-25' : '' }} p-1 align-top"
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
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endif

<script>
const BOARD_URL = '{{ route("shifts.board-data") }}?week={{ $weekParam }}&unit_id={{ $unitId ?? "" }}';
const POLL_MS   = 15000;

async function loadBoard() {
    const spinner = document.getElementById('pollSpinner');
    const lastUpd = document.getElementById('lastUpdate');
    if (spinner) spinner.style.removeProperty('display');

    try {
        const res  = await fetch(BOARD_URL);
        const data = await res.json();

        // Limpar células
        document.querySelectorAll('td[data-period]').forEach(td => {
            td.querySelector('.cell-content').innerHTML =
                '<span class="text-muted small fst-italic empty-label">—</span>';
        });

        // Preencher
        for (const [period, stations] of Object.entries(data)) {
            for (const [stationId, dates] of Object.entries(stations)) {
                for (const [date, employees] of Object.entries(dates)) {
                    const td = document.querySelector(
                        `td[data-period="${period}"][data-station="${stationId}"][data-date="${date}"]`
                    );
                    if (!td) continue;
                    const container = td.querySelector('.cell-content');
                    container.innerHTML = '';
                    employees.forEach(emp => {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-light text-dark border small d-block text-start text-truncate';
                        badge.style.maxWidth = '100%';
                        badge.title  = emp.name;
                        badge.textContent = emp.name;
                        container.appendChild(badge);
                    });
                }
            }
        }

        const now  = new Date();
        if (lastUpd) lastUpd.textContent = 'Atualizado ' + now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0') + ':' + now.getSeconds().toString().padStart(2,'0');
    } catch (e) {
        if (lastUpd) lastUpd.textContent = 'Erro ao atualizar';
    } finally {
        if (spinner) spinner.style.display = 'none';
    }
}

loadBoard();
setInterval(loadBoard, POLL_MS);
</script>
@endsection
