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
@push('styles')
<style>
.col-sticky-left {
    position: sticky !important;
    left: 0 !important;
    z-index: 2;
}
thead .col-sticky-left            { background: #212529 !important; }
tbody .col-sticky-left            { background: #fff !important; }
tfoot .col-sticky-left            { background: #e2e3e5 !important; }

.col-sticky-right {
    position: sticky !important;
    right: 0 !important;
    z-index: 2;
}
thead .col-sticky-right           { background: #212529 !important; }
tbody .col-sticky-right           { background: #fff !important; }
tfoot .col-sticky-right           { background: #e2e3e5 !important; }
</style>
@endpush
@endsection
