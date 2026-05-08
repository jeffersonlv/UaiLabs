@extends('layouts.app')
@section('title', 'Escala — Timeline')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Escala de Funcionários</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('shifts.calendar', array_filter(['unit_id'=>$unitId])) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-calendar-month me-1"></i>Calendário
        </a>
        <a href="{{ route('shifts.templates.index') }}" class="btn btn-outline-secondary btn-sm">Templates</a>
        @if(auth()->user()->isManagerOrAbove())
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#shiftModal">+ Turno</button>
        @endif
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('shifts.index') }}" class="d-flex flex-wrap gap-2 mb-3">
    <select name="unit_id" class="form-select form-select-sm" style="width:200px" onchange="this.form.submit()">
        @foreach($units as $u)
            <option value="{{ $u->id }}" {{ $unitId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
        @endforeach
    </select>
    <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm" style="width:150px" onchange="this.form.submit()">
    @foreach(['day'=>'Dia','week'=>'Semana','month'=>'Mês'] as $v => $l)
        <a href="{{ route('shifts.index', ['unit_id'=>$unitId,'date'=>$date,'view'=>$v]) }}"
           class="btn btn-sm {{ $view === $v ? 'btn-secondary' : 'btn-outline-secondary' }}">{{ $l }}</a>
    @endforeach
</form>

<div class="row g-4">
    {{-- Timeline --}}
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-2">
                <div id="timeline" style="height:500px"></div>
            </div>
        </div>
    </div>

    {{-- Summary panel --}}
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Resumo</div>
            <div class="card-body p-2" id="summary-panel">
                <p class="text-muted small text-center mt-3">Selecione unidade e período</p>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Criar turno --}}
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

@php
    $shiftsJson = json_encode($shifts->map(fn($s) => [
        'id'        => $s->id,
        'group'     => $s->user_id,
        'content'   => $s->typeLabel() . ($s->notes ? ': ' . $s->notes : ''),
        'start'     => $s->start_at->toIso8601String(),
        'end'       => $s->end_at->toIso8601String(),
        'className' => 'shift-' . $s->type,
        'title'     => $s->user->name . '<br>' . $s->start_at->format('H:i') . '–' . $s->end_at->format('H:i'),
    ]), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $groupsJson = json_encode($unitUsers->map(fn($u) => ['id' => $u->id, 'content' => $u->name]));
@endphp
@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/vis-timeline@7.7.3/styles/vis-timeline-graph2d.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/vis-timeline@7.7.3/standalone/umd/vis-timeline-graph2d.min.js"></script>
<script>
(function () {
    var shiftsData = {!! $shiftsJson !!};

    var groups = {!! $groupsJson !!};

    var container = document.getElementById('timeline');
    var items  = new vis.DataSet(shiftsData);
    var groupSet = new vis.DataSet(groups);

    var startDate = new Date('{!! $start->toIso8601String() !!}');
    var endDate   = new Date('{!! $end->toIso8601String() !!}');

    var timeline = new vis.Timeline(container, items, groupSet, {
        start: startDate,
        end:   endDate,
        orientation: 'top',
        showCurrentTime: true,
        zoomMin: 1000 * 60 * 60,
        zoomMax: 1000 * 60 * 60 * 24 * 31,
        tooltip: { followMouse: true },
    });

    // Open show modal on click
    timeline.on('click', function (props) {
        if (props.item) {
            window.location.href = '/shifts/' + props.item;
        }
    });

    // Add shift form submit
    document.getElementById('shiftForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        var body = {};
        fd.forEach(function (v, k) { body[k] = v; });

        fetch('{{ route("shifts.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
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

    // Load summary
    if ('{{ $unitId }}') {
        fetch('{{ route("shifts.summary") }}?unit_id={{ $unitId }}&start_date={{ $date }}&end_date={{ $date }}')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var html = data.map(function (u) {
                var w = u.totals.worked_minutes;
                var s = u.totals.scheduled_minutes;
                var diff = w - s;
                var color = diff >= 0 ? 'success' : 'danger';
                var wh = Math.floor(w/60) + 'h' + (w%60 ? ' ' + (w%60) + 'min' : '');
                return '<div class="border-bottom py-2 px-1">' +
                    '<div class="fw-semibold small">' + u.user + '</div>' +
                    '<div class="d-flex justify-content-between small text-muted">' +
                    '<span>Trabalhado: <strong class="text-' + color + '">' + wh + '</strong></span>' +
                    '</div></div>';
            }).join('');
            document.getElementById('summary-panel').innerHTML = html || '<p class="text-muted small text-center mt-3">Sem dados.</p>';
        });
    }
})();
</script>
<style>
.shift-work     { background:#0d6efd;border-color:#0a58ca;color:#fff; }
.shift-vacation { background:#198754;border-color:#146c43;color:#fff; }
.shift-leave    { background:#ffc107;border-color:#ffca2c;color:#212529; }
.shift-holiday  { background:#6c757d;border-color:#565e64;color:#fff; }
</style>
@endpush
@endsection