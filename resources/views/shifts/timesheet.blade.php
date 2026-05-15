@extends('layouts.app')
@section('title', 'Planilha de Escala')
@section('content')

@php
$dayLabels = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
$today     = \Carbon\Carbon::today()->toDateString();
$prevWeek  = \Carbon\Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek()->subWeek()->format('o-\WW');
$nextWeek  = \Carbon\Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek()->addWeek()->format('o-\WW');
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-table me-2"></i>Planilha de Escala</h4>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('shifts.board', array_filter(['week'=>$weekParam,'unit_id'=>$unitId])) }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-grid-3x3-gap me-1"></i>Quadro
        </a>
        <a href="{{ route('shifts.calendar', array_filter(['unit_id'=>$unitId])) }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-calendar-month me-1"></i>Calendário
        </a>
        @if($isManager)
        <a href="{{ route('shifts.templates.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-layout-text-window me-1"></i>Templates
        </a>
        <a href="{{ route('stations.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-geo-alt me-1"></i>Estações
        </a>
        @endif
    </div>
</div>

{{-- Filtros --}}
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    {{-- Navegação de semana --}}
    <a href="{{ route('shifts.timesheet', array_filter(['week'=>$prevWeek,'unit_id'=>$unitId])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
    <span class="fw-semibold small">
        {{ $weekStart->format('d/m') }} – {{ $weekEnd->format('d/m/Y') }}
    </span>
    <a href="{{ route('shifts.timesheet', array_filter(['week'=>$nextWeek,'unit_id'=>$unitId])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
    <a href="{{ route('shifts.timesheet', array_filter(['unit_id'=>$unitId])) }}"
       class="btn btn-sm btn-outline-primary">Hoje</a>

    @if($units->isNotEmpty())
    <form method="GET" action="{{ route('shifts.timesheet') }}" class="d-flex gap-1 ms-2">
        <input type="hidden" name="week" value="{{ $weekParam }}">
        <select name="unit_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
            <option value="">Todas as unidades</option>
            @foreach($units as $u)
            <option value="{{ $u->id }}" {{ $unitId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </form>
    @endif
</div>

{{-- Tabela --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0" style="overflow-x:auto">
        <table class="table table-bordered table-sm mb-0" style="min-width:1100px">
            <thead class="table-dark">
                <tr>
                    <th style="width:160px">Funcionário</th>
                    @foreach($days as $i => $day)
                    <th class="text-center {{ $day->toDateString() === $today ? 'table-primary text-dark' : '' }}"
                        style="min-width:155px">
                        {{ $dayLabels[$i] }}<br>
                        <small class="fw-normal opacity-75">{{ $day->format('d/m') }}</small>
                    </th>
                    @endforeach
                    <th class="text-center" style="width:90px">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $usr)
                <tr data-user-id="{{ $usr->id }}">
                    <td class="fw-semibold align-middle small">{{ $usr->name }}</td>

                    @foreach($days as $day)
                    @php
                        $key         = $usr->id . '_' . $day->toDateString();
                        $dayShifts   = $shifts->get($key, collect());
                        $isToday     = $day->toDateString() === $today;
                        $isDayOff    = $dayShifts->contains(fn($s) => in_array($s->type, ['leave','vacation','holiday']));
                    @endphp
                    <td class="{{ $isToday ? 'table-primary bg-opacity-25' : '' }} p-1 align-top"
                        data-user="{{ $usr->id }}" data-date="{{ $day->toDateString() }}">

                        @if($isDayOff)
                        @php $offShift = $dayShifts->first(fn($s) => in_array($s->type, ['leave','vacation','holiday'])); @endphp
                        <div class="d-flex align-items-center gap-1 p-1">
                            <span class="badge bg-warning text-dark small">
                                {{ \App\Models\Shift::TYPES[$offShift->type]['label'] }}
                            </span>
                            @if($isManager)
                            <button class="btn btn-link btn-sm p-0 text-danger ms-auto"
                                    onclick="deleteShift({{ $offShift->id }}, this)">
                                <i class="bi bi-x-lg" style="font-size:.7rem"></i>
                            </button>
                            @endif
                        </div>
                        @else
                            {{-- Turnos de trabalho --}}
                            <div class="shift-list" data-user="{{ $usr->id }}" data-date="{{ $day->toDateString() }}">
                            @foreach($dayShifts->where('type','work') as $shift)
                            <div class="shift-entry d-flex align-items-center gap-1 mb-1 small" data-shift-id="{{ $shift->id }}">
                                @if($isManager)
                                <input type="time" class="form-control form-control-sm p-0 px-1 time-input start-input"
                                       value="{{ $shift->start_at->format('H:i') }}"
                                       data-field="start_at" data-shift="{{ $shift->id }}"
                                       style="width:72px;font-size:.75rem">
                                <input type="time" class="form-control form-control-sm p-0 px-1 time-input end-input"
                                       value="{{ $shift->end_at->format('H:i') }}"
                                       data-field="end_at" data-shift="{{ $shift->id }}"
                                       style="width:72px;font-size:.75rem">
                                @else
                                <span class="text-muted" style="font-size:.75rem">{{ $shift->start_at->format('H:i') }}–{{ $shift->end_at->format('H:i') }}</span>
                                @endif

                                @if($stations->isNotEmpty())
                                @if($isManager)
                                <select class="form-select form-select-sm p-0 px-1 station-select"
                                        data-shift="{{ $shift->id }}"
                                        style="width:90px;font-size:.7rem">
                                    <option value="">—</option>
                                    @foreach($stations as $st)
                                    <option value="{{ $st->id }}"
                                            {{ $shift->station_id == $st->id ? 'selected' : '' }}
                                            style="color:{{ $st->color }}">{{ $st->name }}</option>
                                    @endforeach
                                </select>
                                @elseif($shift->station)
                                <span class="badge rounded-pill small"
                                      style="background:{{ $shift->station->color }}20;color:{{ $shift->station->color }};border:1px solid {{ $shift->station->color }}">
                                    {{ $shift->station->name }}
                                </span>
                                @endif
                                @endif

                                @if($isManager)
                                <button class="btn btn-link btn-sm p-0 text-danger ms-auto"
                                        onclick="deleteShift({{ $shift->id }}, this)">
                                    <i class="bi bi-x-lg" style="font-size:.65rem"></i>
                                </button>
                                @endif
                            </div>
                            @endforeach
                            </div>

                            @if($isManager)
                            <div class="d-flex gap-1 mt-1">
                                <button class="btn btn-outline-primary btn-sm py-0 px-1 add-shift-btn"
                                        style="font-size:.7rem"
                                        data-user="{{ $usr->id }}"
                                        data-date="{{ $day->toDateString() }}"
                                        data-unit="{{ $unitId ?? '' }}">
                                    <i class="bi bi-plus"></i> Turno
                                </button>
                                <button class="btn btn-outline-warning btn-sm py-0 px-1 dayoff-btn"
                                        style="font-size:.7rem"
                                        data-user="{{ $usr->id }}"
                                        data-date="{{ $day->toDateString() }}"
                                        data-unit="{{ $unitId ?? '' }}">
                                    <i class="bi bi-moon"></i>
                                </button>
                            </div>
                            @endif
                        @endif
                    </td>
                    @endforeach

                    <td class="text-center align-middle fw-semibold small total-cell"
                        data-user="{{ $usr->id }}">—</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($days) + 2 }}" class="text-center text-muted py-4">
                        Nenhum funcionário encontrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="table-secondary">
                <tr>
                    <td colspan="{{ count($days) + 1 }}" class="text-end fw-semibold small pe-3">Total geral da semana</td>
                    <td class="text-center fw-bold small" id="grandTotal">—</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- ── Quadro de Alocação ────────────────────────────────────────────────────── --}}
@php
$boardPeriods = ['manha' => ['label'=>'Manhã','icon'=>'bi-sunrise','hint'=>'até 12h'],
                 'tarde' => ['label'=>'Tarde','icon'=>'bi-sun',    'hint'=>'12h–18h'],
                 'noite' => ['label'=>'Noite','icon'=>'bi-moon-stars','hint'=>'18h+']];
@endphp

<div class="d-flex align-items-center gap-2 mt-4 mb-2">
    <h5 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Quadro de Alocação</h5>
    <span class="small fw-semibold text-muted">{{ $weekStart->format('d/m') }} – {{ $weekEnd->format('d/m/Y') }}</span>
    <span class="ms-auto small text-muted" id="boardLastUpdate"></span>
</div>

@if($stations->isEmpty())
<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    Nenhuma estação cadastrada.
    @if($isManager)
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
                    @foreach($days as $i => $day)
                    <th class="text-center small {{ $day->toDateString() === $today ? 'table-primary' : '' }}">
                        {{ $dayLabels[$i] }}<br>
                        <span class="fw-normal opacity-75" style="font-size:.7rem">{{ $day->format('d/m') }}</span>
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
                    @foreach($days as $day)
                    <td class="p-1 align-top {{ $day->toDateString() === $today ? 'table-primary bg-opacity-25' : '' }}"
                        data-period="{{ $periodKey }}"
                        data-station="{{ $station->id }}"
                        data-date="{{ $day->toDateString() }}">
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

<script>
(function () {
    var BOARD_URL = '{{ route("shifts.board-data") }}?week={{ $weekParam }}&unit_id={{ $unitId ?? "" }}';
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
                Object.entries(data).forEach(function([period, stations]) {
                    Object.entries(stations).forEach(function([stationId, dates]) {
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
                                b.title = emp.name;
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
</script>

@if($isManager)
<script>
const CSRF = '{{ csrf_token() }}';

// ── Calcular totais ──────────────────────────────────────────────────────────
function fmtMinutes(m) {
    return String(Math.floor(m/60)).padStart(2,'0') + ':' + String(m%60).padStart(2,'0');
}

function recalcTotals() {
    let grand = 0;
    document.querySelectorAll('tbody tr[data-user-id]').forEach(row => {
        let total = 0;
        row.querySelectorAll('.shift-entry').forEach(entry => {
            const s = entry.querySelector('.start-input')?.value;
            const e = entry.querySelector('.end-input')?.value;
            if (s && e) {
                const [sh,sm] = s.split(':').map(Number);
                const [eh,em] = e.split(':').map(Number);
                const diff = (eh*60+em) - (sh*60+sm);
                if (diff > 0) total += diff;
            }
        });
        const uid  = row.dataset.userId;
        const cell = document.querySelector(`.total-cell[data-user="${uid}"]`);
        if (cell) cell.textContent = total > 0 ? fmtMinutes(total) : '—';
        grand += total;
    });
    document.getElementById('grandTotal').textContent = grand > 0 ? fmtMinutes(grand) : '—';
}

// ── Adicionar turno ──────────────────────────────────────────────────────────
document.querySelectorAll('.add-shift-btn').forEach(btn => {
    btn.addEventListener('click', () => addShift(btn.dataset.user, btn.dataset.date, btn.dataset.unit, 'work'));
});

document.querySelectorAll('.dayoff-btn').forEach(btn => {
    btn.addEventListener('click', () => addShift(btn.dataset.user, btn.dataset.date, btn.dataset.unit, 'leave'));
});

async function addShift(userId, date, unitId, type) {
    const startAt = date + (type === 'work' ? ' 08:00:00' : ' 00:00:00');
    const endAt   = date + (type === 'work' ? ' 17:00:00' : ' 23:59:00');

    const body = new FormData();
    body.append('_token',   CSRF);
    body.append('user_id',  userId);
    body.append('start_at', startAt);
    body.append('end_at',   endAt);
    body.append('type',     type);
    if (unitId) body.append('unit_id', unitId);

    const res  = await fetch('{{ route("shifts.store") }}', {method:'POST', body});
    const json = await res.json();

    if (json.ok) {
        location.reload(); // reload simples para garantir consistência
    } else {
        alert('Erro ao salvar turno.');
    }
}

// ── Atualizar campo do turno ────────────────────────────────────────────────
document.querySelectorAll('.time-input').forEach(input => {
    input.addEventListener('change', async function() {
        const shiftId = this.dataset.shift;
        const field   = this.dataset.field;
        const row     = this.closest('tr[data-user-id]');
        const date    = this.closest('td[data-date]').dataset.date;
        const val     = date + ' ' + this.value + ':00';

        const body = new FormData();
        body.append('_token',    CSRF);
        body.append('_method',   'PUT');
        body.append(field,       val);
        // Reenviar campos obrigatórios do StoreShiftRequest
        const entry = this.closest('.shift-entry');
        const s = entry.querySelector('.start-input').value;
        const e = entry.querySelector('.end-input').value;
        body.append('start_at', date + ' ' + s + ':00');
        body.append('end_at',   date + ' ' + e + ':00');
        body.append('user_id',  row.dataset.userId);
        body.append('type',     'work');

        const res = await fetch('/shifts/' + shiftId, {method:'POST', body});
        if (res.ok) recalcTotals();
    });
});

// ── Atualizar estação ────────────────────────────────────────────────────────
document.querySelectorAll('.station-select').forEach(sel => {
    sel.addEventListener('change', async function() {
        const shiftId = this.dataset.shift;
        const entry   = this.closest('.shift-entry');
        const row     = this.closest('tr[data-user-id]');
        const date    = this.closest('td[data-date]').dataset.date;
        const s       = entry.querySelector('.start-input').value;
        const e       = entry.querySelector('.end-input').value;

        const body = new FormData();
        body.append('_token',     CSRF);
        body.append('_method',    'PUT');
        body.append('station_id', this.value);
        body.append('start_at',   date + ' ' + s + ':00');
        body.append('end_at',     date + ' ' + e + ':00');
        body.append('user_id',    row.dataset.userId);
        body.append('type',       'work');

        await fetch('/shifts/' + shiftId, {method:'POST', body});
    });
});

// ── Apagar turno ─────────────────────────────────────────────────────────────
async function deleteShift(id, btn) {
    if (!confirm('Remover este turno?')) return;
    const body = new FormData();
    body.append('_token',  CSRF);
    body.append('_method', 'DELETE');
    const res = await fetch('/shifts/' + id, {method:'POST', body});
    if (res.ok) {
        const entry = btn.closest('.shift-entry, div');
        entry?.remove();
        recalcTotals();
    }
}

// Calcular na carga da página
recalcTotals();
</script>
@else
<script>
function fmtMinutes(m){return String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0');}
function recalcTotals(){
    let grand=0;
    document.querySelectorAll('tbody tr[data-user-id]').forEach(row=>{
        let total=0;
        row.querySelectorAll('.shift-entry').forEach(entry=>{
            const times=entry.querySelectorAll('span');
            const txt=entry.querySelector('span')?.textContent||'';
            const m=txt.match(/(\d{2}):(\d{2})–(\d{2}):(\d{2})/);
            if(m){const diff=(+m[3]*60+ +m[4])-(+m[1]*60+ +m[2]);if(diff>0)total+=diff;}
        });
        const uid=row.dataset.userId;
        const cell=document.querySelector(`.total-cell[data-user="${uid}"]`);
        if(cell)cell.textContent=total>0?fmtMinutes(total):'—';
        grand+=total;
    });
    document.getElementById('grandTotal').textContent=grand>0?fmtMinutes(grand):'—';
}
recalcTotals();
</script>
@endif
@endsection
