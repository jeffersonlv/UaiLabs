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
        @if($canBoard)
        <a href="#board" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-grid-3x3-gap me-1"></i>Quadro
        </a>
        @endif
        <a href="{{ route('shifts.calendar', array_filter(['unit_id'=>$unitId])) }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-calendar-month me-1"></i>Calendário
        </a>
        @if($isManager)
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#saveTemplateModal">
            <i class="bi bi-floppy me-1"></i>Salvar semana
        </button>
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
                    <th class="col-sticky-left" style="width:160px">Funcionário</th>
                    @foreach($days as $i => $day)
                    <th class="text-center {{ $day->toDateString() === $today ? 'table-primary text-dark' : '' }}"
                        style="min-width:155px">
                        {{ $dayLabels[$i] }}<br>
                        <small class="fw-normal opacity-75">{{ $day->format('d/m') }}</small>
                    </th>
                    @endforeach
                    <th class="text-center col-sticky-right" style="width:90px">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $usr)
                <tr data-user-id="{{ $usr->id }}">
                    <td class="fw-semibold align-middle small col-sticky-left">{{ $usr->name }}</td>

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
                                <button class="btn btn-link btn-sm p-0 text-danger ms-auto"
                                        onclick="deleteShift({{ $shift->id }}, this)">
                                    <i class="bi bi-x-lg" style="font-size:.65rem"></i>
                                </button>
                                @else
                                <span class="text-muted" style="font-size:.75rem">{{ $shift->start_at->format('H:i') }}–{{ $shift->end_at->format('H:i') }}</span>
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

                    <td class="text-center align-middle fw-semibold small total-cell col-sticky-right"
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
                    <td class="text-center fw-bold small col-sticky-right" id="grandTotal">—</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- ── Templates ────────────────────────────────────────────────────────────── --}}
@if($isManager)
<div class="d-flex align-items-center gap-2 mt-4 mb-2">
    <h5 class="mb-0"><i class="bi bi-layout-text-window me-2"></i>Templates</h5>
    <a href="{{ route('shifts.templates.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-gear me-1"></i>Gerenciar
    </a>
</div>

@if($templates->isEmpty())
<div class="alert alert-light border py-2 small text-muted">Nenhum template salvo ainda.</div>
@else
@php $periodLabels = ['weekly'=>'Semanal','biweekly'=>'Quinzenal','monthly'=>'Mensal']; @endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        @foreach($templates as $tpl)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom flex-wrap">
            <span class="fw-semibold">{{ $tpl->name }}</span>
            <span class="badge bg-secondary-subtle text-secondary">
                {{ $periodLabels[$tpl->period] ?? $tpl->period }}
            </span>
            <span class="text-muted small">{{ count($tpl->config ?? []) }} turno(s)</span>

            <form method="POST" action="{{ route('shifts.templates.apply', $tpl) }}"
                  class="d-flex gap-2 align-items-center ms-auto flex-wrap">
                @csrf
                <input type="hidden" name="start_date" value="{{ $weekStart->toDateString() }}">
                <select name="conflict" class="form-select form-select-sm" style="width:130px">
                    <option value="skip">Manter existentes</option>
                    <option value="replace">Substituir</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary"
                        onclick="return confirm('Aplicar template \'{{ addslashes($tpl->name) }}\' na semana {{ $weekStart->format('d/m') }}?')">
                    <i class="bi bi-play-fill me-1"></i>Aplicar
                </button>
            </form>

            <form method="POST" action="{{ route('shifts.templates.destroy', $tpl) }}"
                  onsubmit="return confirm('Excluir template \'{{ addslashes($tpl->name) }}\'?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger py-0 px-2">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Modal: Salvar semana como template --}}
<div class="modal fade" id="saveTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="{{ route('shifts.timesheet.save-template') }}">
            @csrf
            <input type="hidden" name="week" value="{{ $weekParam }}">
            <input type="hidden" name="unit_id" value="{{ $unitId ?? '' }}">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Salvar semana como template</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nome do template</label>
                        <input type="text" name="name" class="form-control form-control-sm"
                               required maxlength="100"
                               placeholder="ex: Semana padrão verão">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Repetição</label>
                        <select name="period" class="form-select form-select-sm">
                            <option value="weekly">Semanal</option>
                            <option value="biweekly">Quinzenal</option>
                            <option value="monthly">Mensal (4 semanas)</option>
                        </select>
                    </div>
                    <div class="form-text mt-2">
                        Serão salvos os <strong>{{ $shifts->flatten()->count() }}</strong> turno(s) desta semana
                        ({{ $weekStart->format('d/m') }}–{{ $weekEnd->format('d/m') }}).
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 mt-3">
    {{ session('success') }}
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
@endif

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


// ── Apagar turno ─────────────────────────────────────────────────────────────
async function deleteShift(id, btn) {
    if (!confirm('Remover este turno?')) return;
    const body = new FormData();
    body.append('_token',  CSRF);
    body.append('_method', 'DELETE');
    const res = await fetch('/shifts/' + id, {method:'POST', body});
    if (res.ok) {
        const entry = btn.closest('.shift-entry');
        if (entry) { entry.remove(); recalcTotals(); }
        else        { location.reload(); }
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
{{-- ── Quadro de Alocação ───────────────────────────────────────────────────── --}}
@if($canBoard)
@php
$usersWithShift = $shifts->keys()->map(fn($k) => (int)explode('_',$k)[0])->unique()->flip()->toArray();
$boardPeriods = [
    'manha' => ['label'=>'Manhã', 'hint'=>'até 12h', 'icon'=>'bi-sunrise'],
    'tarde'  => ['label'=>'Tarde', 'hint'=>'12h–18h', 'icon'=>'bi-sun'],
    'noite'  => ['label'=>'Noite', 'hint'=>'18h+',    'icon'=>'bi-moon-stars'],
];
@endphp
<div id="board" class="mt-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Quadro de Alocação</h5>
        <small class="text-muted">{{ $weekStart->format('d/m') }} – {{ $weekEnd->format('d/m/Y') }}</small>
    </div>

    @if($stations->isEmpty())
    <div class="alert alert-info py-2 small">
        Nenhuma estação cadastrada.
        @if($isManager) <a href="{{ route('stations.index') }}">Cadastre estações</a> para usar o quadro. @endif
    </div>
    @else

    {{-- Mobile: painel de funcionários collapsível --}}
    <div class="d-md-none mb-3">
        <button class="btn btn-sm btn-outline-secondary w-100 text-start d-flex align-items-center"
                data-bs-toggle="collapse" data-bs-target="#empPanelMobile" aria-expanded="false">
            <i class="bi bi-people me-2"></i><span>Funcionários</span>
            <i class="bi bi-chevron-down ms-auto"></i>
        </button>
        <div class="collapse" id="empPanelMobile">
            <div class="border rounded p-2 mt-1 bg-white">
                <div class="d-flex flex-wrap gap-1 mb-1">
                    @foreach($users as $emp)
                    <span class="alloc-employee badge {{ isset($usersWithShift[$emp->id]) ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-secondary-subtle text-secondary-emphasis border' }}"
                          data-user-id="{{ $emp->id }}" data-name="{{ $emp->name }}"
                          draggable="{{ $isManager ? 'true' : 'false' }}"
                          style="font-size:.72rem;cursor:{{ $isManager ? 'grab' : 'default' }}">
                        {{ $emp->name }}@if(isset($usersWithShift[$emp->id])) <i class="bi bi-check2"></i>@endif
                    </span>
                    @endforeach
                </div>
                <small class="text-muted" style="font-size:.68rem"><i class="bi bi-check2 text-success"></i> tem turno na semana</small>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 align-items-start">

        {{-- Desktop: painel lateral --}}
        <div class="flex-shrink-0 d-none d-md-block" style="width:155px">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-2 bg-white border-bottom">
                    <small class="fw-semibold text-muted text-uppercase" style="font-size:.68rem;letter-spacing:.05em">Funcionários</small>
                </div>
                <div class="card-body p-2" style="max-height:480px;overflow-y:auto">
                    @foreach($users as $emp)
                    <div class="alloc-employee mb-1" data-user-id="{{ $emp->id }}" data-name="{{ $emp->name }}"
                         draggable="{{ $isManager ? 'true' : 'false' }}">
                        <span class="badge d-block text-start px-2 py-1 w-100
                              {{ isset($usersWithShift[$emp->id]) ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-secondary-subtle text-secondary-emphasis border' }}"
                              style="font-size:.7rem;cursor:{{ $isManager ? 'grab' : 'default' }};white-space:normal;word-break:break-word">
                            {{ $emp->name }}
                            @if(isset($usersWithShift[$emp->id]))<i class="bi bi-check2 ms-1 opacity-75"></i>@endif
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            <small class="text-muted d-block mt-1" style="font-size:.68rem">
                <i class="bi bi-check2 text-success"></i> tem turno na semana
            </small>
        </div>

        {{-- Tabelas por período --}}
        <div class="flex-grow-1" style="min-width:0;overflow:hidden">
            @foreach($boardPeriods as $periodKey => $period)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
                    <i class="bi {{ $period['icon'] }} text-primary"></i>
                    <span class="fw-semibold">{{ $period['label'] }}</span>
                    <small class="text-muted">({{ $period['hint'] }})</small>
                </div>
                <div class="card-body p-0" style="overflow-x:auto">
                    <table class="table table-bordered table-sm mb-0 board-table" style="min-width:480px">
                        <thead class="table-light">
                            <tr>
                                <th class="board-col-sticky" style="min-width:90px">Estação</th>
                                @foreach($days as $i => $day)
                                <th class="text-center {{ $day->toDateString() === $today ? 'table-primary' : '' }}" style="min-width:88px">
                                    {{ $dayLabels[$i] }}&nbsp;{{ $day->format('d/m') }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stations as $station)
                            <tr>
                                <td class="board-col-sticky align-middle fw-semibold small">
                                    <span class="d-flex align-items-center gap-1">
                                        <span class="rounded-circle flex-shrink-0"
                                              style="width:8px;height:8px;display:inline-block;background:{{ $station->color }}"></span>
                                        {{ $station->name }}
                                    </span>
                                </td>
                                @foreach($days as $day)
                                @php $cellAllocs = $boardAllocations[$periodKey][$station->id][$day->toDateString()] ?? []; @endphp
                                <td class="{{ $day->toDateString() === $today ? 'table-primary bg-opacity-25' : '' }} p-1 align-top alloc-drop-cell"
                                    data-period="{{ $periodKey }}"
                                    data-station="{{ $station->id }}"
                                    data-date="{{ $day->toDateString() }}">
                                    <div class="alloc-cell-content d-flex flex-column gap-1">
                                        @forelse($cellAllocs as $alloc)
                                        <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle d-flex align-items-center justify-content-between alloc-badge"
                                              data-alloc-id="{{ $alloc['id'] }}"
                                              data-user-id="{{ $alloc['user_id'] }}"
                                              data-name="{{ $alloc['name'] }}"
                                              draggable="{{ $isManager ? 'true' : 'false' }}"
                                              style="font-size:.65rem;max-width:100%;cursor:{{ $isManager ? 'grab' : 'default' }}">
                                            <span class="text-truncate" style="min-width:0">{{ $alloc['name'] }}</span>
                                            @if($isManager)
                                            <button class="alloc-remove-btn btn btn-link p-0 ms-1 text-danger lh-1 flex-shrink-0"
                                                    data-alloc-id="{{ $alloc['id'] }}"
                                                    style="font-size:.75rem">×</button>
                                            @endif
                                        </span>
                                        @empty
                                        <span class="text-muted small fst-italic alloc-empty">—</span>
                                        @endforelse
                                        @if($isManager)
                                        <button class="alloc-add-btn btn btn-outline-secondary btn-sm p-0 mt-1"
                                                data-period="{{ $periodKey }}"
                                                data-station="{{ $station->id }}"
                                                data-date="{{ $day->toDateString() }}"
                                                title="Adicionar funcionário"
                                                style="font-size:.65rem;line-height:1.5;border-style:dashed">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                        @endif
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
        </div>
    </div>

    {{-- Picker flutuante (manager only) --}}
    @if($isManager)
    <div id="allocPicker" class="card shadow-lg border p-0"
         style="display:none;position:fixed;z-index:1060;min-width:175px;max-width:210px">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <small class="fw-semibold text-muted">Adicionar funcionário</small>
                <button id="allocPickerClose" class="btn-close" style="font-size:.6rem"></button>
            </div>
            <input type="text" id="allocPickerSearch" class="form-control form-control-sm mb-1" placeholder="Buscar...">
            <div id="allocPickerList" style="max-height:210px;overflow-y:auto">
                @foreach($users as $emp)
                <button class="btn btn-sm text-start w-100 py-1 px-2 rounded alloc-pick-btn
                               {{ isset($usersWithShift[$emp->id]) ? 'text-success fw-semibold' : 'text-secondary' }}"
                        data-user-id="{{ $emp->id }}" data-name="{{ $emp->name }}">
                    {{ $emp->name }}
                    @if(isset($usersWithShift[$emp->id]))<i class="bi bi-check2 ms-1 opacity-75" style="font-size:.7rem"></i>@endif
                </button>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @endif {{-- /stations --}}
</div>
@endif

@if($canBoard && $isManager)
<script>
const ALLOC_STORE_URL   = '{{ route("board-allocations.store") }}';
const ALLOC_DESTROY_URL = '{{ route("board-allocations.destroy", ":id") }}';
const ALLOC_UNIT_ID     = '{{ $unitId ?? "" }}';

// ── Estado do drag ──────────────────────────────────────────────────────────
// type: 'employee' | 'badge'
let allocDrag = null;

// ── Drag dos funcionários (painel) ──────────────────────────────────────────
document.querySelectorAll('.alloc-employee').forEach(el => {
    el.addEventListener('dragstart', function(e) {
        allocDrag = {type:'employee', userId: this.dataset.userId, name: this.dataset.name};
        e.dataTransfer.effectAllowed = 'copy';
    });
    el.addEventListener('dragend', () => { allocDrag = null; });
});

// ── Drag dos badges (mover entre células) ────────────────────────────────────
function wireBadgeDrag(badge) {
    if (badge.getAttribute('draggable') !== 'true') return;
    badge.addEventListener('dragstart', function(e) {
        e.stopPropagation();
        allocDrag = {
            type:     'badge',
            userId:   this.dataset.userId,
            name:     this.dataset.name,
            allocId:  this.dataset.allocId,
            sourceTd: this.closest('.alloc-drop-cell'),
        };
        e.dataTransfer.effectAllowed = 'move';
        setTimeout(() => { this.style.opacity = '.35'; }, 0);
    });
    badge.addEventListener('dragend', function() {
        this.style.opacity = '';
        allocDrag = null;
    });
}
document.querySelectorAll('.alloc-badge').forEach(wireBadgeDrag);

// ── Células de drop ──────────────────────────────────────────────────────────
function wireDropCell(td) {
    td.addEventListener('dragover', e => {
        if (!allocDrag) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = allocDrag.type === 'badge' ? 'move' : 'copy';
        td.classList.add('alloc-drop-hover');
    });
    td.addEventListener('dragleave', e => {
        if (!td.contains(e.relatedTarget)) td.classList.remove('alloc-drop-hover');
    });
    td.addEventListener('drop', async function(e) {
        e.preventDefault();
        this.classList.remove('alloc-drop-hover');
        if (!allocDrag) return;
        if (allocDrag.type === 'badge' && allocDrag.sourceTd === this) return;

        const body = new FormData();
        body.append('_token',     CSRF);
        body.append('user_id',    allocDrag.userId);
        body.append('station_id', this.dataset.station);
        body.append('date',       this.dataset.date);
        body.append('period',     this.dataset.period);
        if (ALLOC_UNIT_ID) body.append('unit_id', ALLOC_UNIT_ID);

        const res  = await fetch(ALLOC_STORE_URL, {method:'POST', body});
        const json = await res.json();
        if (!json.ok) return;

        if (allocDrag.type === 'badge' && allocDrag.sourceTd) {
            const oldBadge = allocDrag.sourceTd.querySelector(`.alloc-badge[data-alloc-id="${allocDrag.allocId}"]`);
            if (oldBadge) {
                const db = new FormData();
                db.append('_token', CSRF); db.append('_method', 'DELETE');
                await fetch(ALLOC_DESTROY_URL.replace(':id', allocDrag.allocId), {method:'POST', body:db});
                removeBadgeFromDom(oldBadge);
            }
        }
        addAllocBadge(this, json.id, json.name, allocDrag.userId);
    });
}
document.querySelectorAll('.alloc-drop-cell').forEach(wireDropCell);

// ── Adicionar badge na célula ────────────────────────────────────────────────
function addAllocBadge(td, allocId, name, userId) {
    const content = td.querySelector('.alloc-cell-content');
    td.querySelector('.alloc-empty')?.remove();
    const badge = document.createElement('span');
    badge.className = 'badge bg-primary-subtle text-primary-emphasis border border-primary-subtle d-flex align-items-center justify-content-between alloc-badge';
    badge.dataset.allocId = allocId;
    badge.dataset.userId  = userId;
    badge.dataset.name    = name;
    badge.draggable = true;
    badge.style.cssText = 'font-size:.65rem;max-width:100%;cursor:grab';
    badge.innerHTML = `<span class="text-truncate" style="min-width:0">${name}</span>`
        + `<button class="alloc-remove-btn btn btn-link p-0 ms-1 text-danger lh-1 flex-shrink-0" data-alloc-id="${allocId}" style="font-size:.75rem">×</button>`;
    badge.querySelector('.alloc-remove-btn').addEventListener('click', function(e) {
        e.stopPropagation();
        removeAllocBadge(this.dataset.allocId, this.closest('.alloc-badge'));
    });
    wireBadgeDrag(badge);
    const addBtn = content.querySelector('.alloc-add-btn');
    content.insertBefore(badge, addBtn);
}

function removeBadgeFromDom(badgeEl) {
    const content = badgeEl.closest('.alloc-cell-content');
    badgeEl.remove();
    if (content && !content.querySelector('.alloc-badge')) {
        const empty = document.createElement('span');
        empty.className = 'text-muted small fst-italic alloc-empty';
        empty.textContent = '—';
        content.insertBefore(empty, content.querySelector('.alloc-add-btn'));
    }
}

async function removeAllocBadge(id, badgeEl) {
    const body = new FormData();
    body.append('_token', CSRF); body.append('_method', 'DELETE');
    const res = await fetch(ALLOC_DESTROY_URL.replace(':id', id), {method:'POST', body});
    if (res.ok) removeBadgeFromDom(badgeEl);
}

document.querySelectorAll('.alloc-remove-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        removeAllocBadge(this.dataset.allocId, this.closest('.alloc-badge'));
    });
});

// ── Picker flutuante ──────────────────────────────────────────────────────────
let pickerTargetTd = null;
const picker       = document.getElementById('allocPicker');
const pickerSearch = document.getElementById('allocPickerSearch');
const pickerList   = document.getElementById('allocPickerList');

function showPicker(td, anchor) {
    pickerTargetTd = td;
    pickerSearch.value = '';
    pickerList.querySelectorAll('.alloc-pick-btn').forEach(b => b.style.display = '');
    picker.style.display = 'block';

    const rect = anchor.getBoundingClientRect();
    const pw   = picker.offsetWidth  || 195;
    const ph   = picker.offsetHeight || 280;
    let top  = rect.bottom + window.scrollY + 4;
    let left = rect.left   + window.scrollX;

    if (left + pw > window.innerWidth  - 8) left = window.innerWidth  - pw - 8;
    if (top  + ph > window.innerHeight + window.scrollY - 8) top = rect.top + window.scrollY - ph - 4;

    picker.style.top  = Math.max(8, top)  + 'px';
    picker.style.left = Math.max(8, left) + 'px';
    pickerSearch.focus();
}

function closePicker() {
    picker.style.display = 'none';
    pickerTargetTd = null;
}

document.querySelectorAll('.alloc-add-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const td = this.closest('.alloc-drop-cell');
        if (pickerTargetTd === td && picker.style.display !== 'none') { closePicker(); return; }
        showPicker(td, this);
    });
});

document.getElementById('allocPickerClose').addEventListener('click', closePicker);

pickerSearch.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    pickerList.querySelectorAll('.alloc-pick-btn').forEach(b => {
        b.style.display = b.dataset.name.toLowerCase().includes(q) ? '' : 'none';
    });
});

pickerList.querySelectorAll('.alloc-pick-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        if (!pickerTargetTd) return;
        const td = pickerTargetTd;
        closePicker();

        const body = new FormData();
        body.append('_token',     CSRF);
        body.append('user_id',    this.dataset.userId);
        body.append('station_id', td.dataset.station);
        body.append('date',       td.dataset.date);
        body.append('period',     td.dataset.period);
        if (ALLOC_UNIT_ID) body.append('unit_id', ALLOC_UNIT_ID);

        const res  = await fetch(ALLOC_STORE_URL, {method:'POST', body});
        const json = await res.json();
        if (json.ok) addAllocBadge(td, json.id, json.name, this.dataset.userId);
    });
});

document.addEventListener('click', function(e) {
    if (picker.style.display === 'none') return;
    if (!picker.contains(e.target) && !e.target.closest('.alloc-add-btn')) closePicker();
});
</script>
@endif

@push('styles')
<style>
.col-sticky-left {
    position: sticky !important;
    left: 0 !important;
    z-index: 2;
}
thead .col-sticky-left  { background: #212529 !important; }
tbody .col-sticky-left  { background: #fff    !important; }
tfoot .col-sticky-left  { background: #e2e3e5 !important; }

.col-sticky-right {
    position: sticky !important;
    right: 0 !important;
    z-index: 2;
}
thead .col-sticky-right { background: #212529 !important; }
tbody .col-sticky-right { background: #fff    !important; }
tfoot .col-sticky-right { background: #e2e3e5 !important; }

/* Board: coluna Estação sticky */
.board-table .board-col-sticky {
    position: sticky;
    left: 0;
    z-index: 2;
    background: #fff;
}
.board-table thead .board-col-sticky { background: #f8f9fa; z-index: 3; }

.alloc-drop-cell.alloc-drop-hover { background: #cfe2ff !important; outline: 2px dashed #0d6efd; }
.alloc-employee[draggable="true"]  { cursor: grab; }
.alloc-employee[draggable="true"]:active { opacity: .6; }
.alloc-badge[draggable="true"]:active    { opacity: .4; }
.alloc-add-btn { opacity: .55; transition: opacity .15s; }
.alloc-add-btn:hover { opacity: 1; }
</style>
@endpush
@endsection
