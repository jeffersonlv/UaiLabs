@extends('layouts.app')
@section('title', 'Checklist')
@section('content')
<div class="d-flex align-items-baseline justify-content-between mb-1">
    <h4 class="mb-0">Checklist — {{ now()->format('d/m/Y') }}</h4>
    <span class="text-muted small">{{ auth()->user()->name }}</span>
</div>
@if($visibleUnits->count() === 1)
    <p class="text-muted mb-3">
        <i class="bi bi-building me-1"></i>
        <strong>{{ $visibleUnits->first()->name }}</strong>
        <span class="badge bg-secondary ms-1" style="font-size:.65rem">{{ $visibleUnits->first()->typeLabel() }}</span>
    </p>
@else
    <p class="text-muted mb-3">{{ $visibleUnits->pluck('name')->join(' · ') }}</p>
@endif

@php
    // Group: unit → category → subcategory → items
    $byUnit     = $occurrences->groupBy(fn($o) => $o->unit_id ?? 'geral');
    $multiUnit  = $byUnit->count() > 1;
    $statusLabels = ['PENDING'=>'Pendente','DONE'=>'Concluída','OVERDUE'=>'Atrasada','REOPENED'=>'Reaberta'];
    $statusColors = ['PENDING'=>'warning','DONE'=>'success','OVERDUE'=>'danger','REOPENED'=>'info'];
    $accordionId  = 0;
@endphp

@forelse($occurrences->groupBy(fn($o) => $o->activity->category->name ?? 'Sem categoria') as $category => $catItems)
@php
    $accordionId++;
    $catId = 'cat-' . $accordionId;
    $pendingIds = $catItems->whereIn('status', ['PENDING','OVERDUE'])->pluck('id')->toArray();
@endphp
<div class="mb-3">
    {{-- Category header --}}
    <div class="d-flex align-items-center gap-2 mb-1">
        <button class="btn btn-sm btn-outline-secondary py-0 px-2 accordion-toggle-btn"
                data-bs-toggle="collapse" data-bs-target="#{{ $catId }}"
                aria-expanded="true">
            <i class="bi bi-chevron-down" style="font-size:.75rem"></i>
        </button>
        <span class="text-uppercase text-muted fw-semibold" style="font-size:.75rem;letter-spacing:.05em">{{ $category }}</span>
        @if(count($pendingIds))
            <button type="button"
                    class="btn btn-sm btn-outline-primary py-0 px-2 ms-auto bulk-btn"
                    style="font-size:.7rem"
                    data-ids="{{ json_encode($pendingIds) }}"
                    data-count="{{ count($pendingIds) }}"
                    data-category="{{ $category }}">
                <i class="bi bi-check2-all me-1"></i>Marcar todos ({{ count($pendingIds) }})
            </button>
        @endif
    </div>

    <div id="{{ $catId }}" class="collapse show">
        @php
            $bySubcat = $catItems->groupBy(fn($o) => $o->activity->subcategory?->name ?? '');
            $shownDoneDivider = false;
        @endphp

        @foreach($bySubcat as $subcatName => $subcatItems)
            @if($subcatName)
                <p class="text-muted mb-1 ms-1" style="font-size:.7rem">— {{ $subcatName }}</p>
            @endif

            <div class="card border-0 shadow-sm mb-2">
                @php $shownDoneDivider = false; @endphp
                @foreach($subcatItems as $occ)
                @php
                    $act  = $occ->activity;
                    $done = in_array($occ->status, ['DONE', 'REOPENED']);
                    $unitNames = $act->units->pluck('name')->join(', ');
                    $isGeral   = $act->units->isEmpty();
                    $borderColor = match($occ->status) {
                        'DONE','REOPENED' => '#198754',
                        'OVERDUE'         => '#dc3545',
                        default           => '#ffc107',
                    };
                @endphp
                @if($done && !$shownDoneDivider)
                    @php $shownDoneDivider = true; @endphp
                    <div class="px-3 py-1 bg-success bg-opacity-10 border-bottom border-top">
                        <small class="text-success fw-semibold"><i class="bi bi-check2-all me-1"></i>Concluídas</small>
                    </div>
                @endif
                <div class="d-flex align-items-start gap-3 p-3 {{ !$loop->last ? 'border-bottom' : '' }}"
                     style="border-left: 4px solid {{ $borderColor }}">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            @if($act->sequence_required)
                                <span class="badge text-white" style="background:#6f42c1!important">Seq.{{ $act->sequence_order }}</span>
                            @endif
                            <span class="fw-medium {{ $done ? 'text-decoration-line-through text-muted' : '' }}">{{ $act->title }}</span>
                            @if($isGeral)
                                <span class="badge bg-light text-muted border" style="font-size:.65rem;font-weight:400">Geral</span>
                            @else
                                <span class="badge bg-light text-secondary border" style="font-size:.65rem;font-weight:400">
                                    <i class="bi bi-building" style="font-size:.6rem"></i> {{ $unitNames }}
                                </span>
                            @endif
                            @if($multiUnit && $occ->unit)
                                <span class="badge bg-primary bg-opacity-10 text-primary border-0" style="font-size:.6rem">{{ $occ->unit->name }}</span>
                            @endif
                        </div>
                        @if($act->description)
                            <small class="text-muted">{{ $act->description }}</small>
                        @endif
                        @if($occ->logs->isNotEmpty())
                            <div class="mt-1">
                                @foreach($occ->logs->take(2) as $log)
                                    <div class="d-flex align-items-baseline gap-1 small">
                                        @if(str_contains($log->action, 'complete'))
                                            <span class="text-success fw-semibold" style="font-size:.7rem">✓</span>
                                            <span class="text-muted">{{ $log->user?->name ?? '—' }} às {{ $log->done_at->format('H:i') }}</span>
                                        @else
                                            <span class="text-warning fw-semibold" style="font-size:.7rem">↺</span>
                                            <span class="text-muted">{{ $log->user?->name ?? '—' }} às {{ $log->done_at->format('H:i') }}</span>
                                        @endif
                                    </div>
                                @endforeach
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted history-btn"
                                        style="font-size:.7rem" data-id="{{ $occ->id }}">
                                    <i class="bi bi-clock-history"></i> ver histórico completo
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-column align-items-end gap-2" style="min-width:164px">
                        <span class="badge bg-{{ $statusColors[$occ->status] ?? 'secondary' }} {{ $occ->status === 'PENDING' ? 'text-dark' : '' }}">
                            {{ $statusLabels[$occ->status] ?? $occ->status }}
                        </span>

                        @if($done)
                            <div class="stc stc-done" style="width:164px">
                                <div class="stc-track">
                                    <span class="stc-label stc-label-done">Concluída ✓</span>
                                    <div class="stc-handle stc-handle-done">✓</div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('checklist.complete', $occ) }}" class="mt-1">
                                @csrf @method('PATCH')
                                <div class="reexec-wrap" style="width:164px">
                                    <button type="button" class="btn btn-sm btn-outline-warning w-100 reexec-toggle">Reexecutar</button>
                                    <div class="reexec-form mt-1" style="display:none">
                                        <input name="justification" placeholder="Justificativa obrigatória"
                                               class="form-control form-control-sm mb-1" required>
                                        <button type="submit" class="btn btn-sm btn-warning w-100">Confirmar</button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <form method="POST" action="{{ route('checklist.complete', $occ) }}">
                                @csrf @method('PATCH')
                                <div class="stc" style="width:164px" title="Deslize para concluir">
                                    <div class="stc-track">
                                        <span class="stc-label">Deslize para concluir</span>
                                        <div class="stc-handle">&#8250;</div>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
@empty
<p class="text-muted">Nenhuma tarefa para hoje.</p>
@endforelse

{{-- Modal: Bulk Confirm --}}
<div class="modal fade" id="bulkModal" tabindex="-1" aria-labelledby="bulkModalLabel" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center px-4 pb-2">
                <div class="mb-3">
                    <span style="font-size:2.5rem">📋</span>
                </div>
                <h5 class="fw-bold mb-2" id="bulkModalLabel">Marcar todas as tarefas?</h5>
                <p class="text-muted mb-1">
                    Você está prestes a concluir
                    <strong id="bulkCount" class="text-dark"></strong> tarefa(s) pendente(s)
                    da categoria <strong id="bulkCategory" class="text-dark"></strong>.
                </p>
                <p class="text-muted small mb-0">Esta ação pode ser desfeita individualmente usando "Reexecutar" em cada tarefa.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 pt-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary px-4" id="bulkConfirm">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="bulkSpinner"></span>
                    <i class="bi bi-check2-all me-1" id="bulkIcon"></i>Sim, concluir todas
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Occurrence History --}}
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyTitle">Histórico da Tarefa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historyBody">
                <div class="text-center py-3"><span class="spinner-border text-secondary"></span></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
.stc-track { position:relative;height:34px;background:#d1e7dd;border-radius:17px;overflow:hidden;user-select:none;touch-action:none;cursor:grab; }
.stc-track:active { cursor:grabbing; }
.stc-label { position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:.75rem;color:#146c43;pointer-events:none;padding-left:32px;transition:opacity .15s;white-space:nowrap; }
.stc-handle { position:absolute;left:3px;top:3px;width:28px;height:28px;background:#198754;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;line-height:1;pointer-events:none;will-change:left; }
.stc-submitting .stc-track { background:#198754;cursor:default; }
.stc-submitting .stc-handle { background:#fff;color:#198754;font-size:.9rem; }
.stc-done .stc-track { background:#198754;cursor:default;opacity:.85; }
.stc-label-done { color:#fff!important;padding-left:32px;font-weight:500; }
.stc-handle-done { left:calc(100% - 31px)!important;background:#fff!important;color:#198754!important;font-size:.9rem!important; }
</style>
<script>
(function () {
    // ── Slide to confirm ───────────────────────────────────────
    document.querySelectorAll('.stc:not(.stc-done)').forEach(function (widget) {
        var track = widget.querySelector('.stc-track');
        var handle = widget.querySelector('.stc-handle');
        var label = widget.querySelector('.stc-label');
        var form = widget.closest('form');
        var dragging = false, startCX = 0, currentX = 0;
        function maxX() { return track.offsetWidth - handle.offsetWidth - 6; }
        function cx(e) { return e.touches ? e.touches[0].clientX : e.clientX; }
        function setPos(x, anim) { handle.style.transition = anim ? 'left .2s ease' : 'none'; handle.style.left = (3 + x) + 'px'; label.style.opacity = Math.max(0, 1 - x / maxX()); }
        track.addEventListener('mousedown', function (e) { if (widget.classList.contains('stc-submitting')) return; dragging = true; startCX = cx(e) - currentX; e.preventDefault(); });
        track.addEventListener('touchstart', function (e) { if (widget.classList.contains('stc-submitting')) return; dragging = true; startCX = cx(e) - currentX; e.preventDefault(); }, {passive:false});
        document.addEventListener('mousemove', function (e) { if (!dragging) return; currentX = Math.max(0, Math.min(cx(e) - startCX, maxX())); setPos(currentX, false); e.preventDefault(); });
        document.addEventListener('touchmove', function (e) { if (!dragging) return; currentX = Math.max(0, Math.min(cx(e) - startCX, maxX())); setPos(currentX, false); e.preventDefault(); }, {passive:false});
        function onEnd() { if (!dragging) return; dragging = false; if (currentX >= maxX() * 0.82) { currentX = maxX(); setPos(currentX, true); label.style.opacity = 0; widget.classList.add('stc-submitting'); handle.innerHTML = '✓'; setTimeout(function () { form.submit(); }, 280); } else { currentX = 0; setPos(0, true); } }
        document.addEventListener('mouseup', onEnd);
        document.addEventListener('touchend', onEnd);
    });

    // ── Reexecutar toggle ──────────────────────────────────────
    document.querySelectorAll('.reexec-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.nextElementSibling;
            var open = form.style.display !== 'none';
            form.style.display = open ? 'none' : 'block';
            btn.classList.toggle('btn-warning', !open);
            btn.classList.toggle('btn-outline-warning', open);
            if (!open) form.querySelector('input').focus();
        });
    });

    // ── Bulk complete ──────────────────────────────────────────
    var bulkIds = [];
    var bulkModal = new bootstrap.Modal(document.getElementById('bulkModal'));

    document.querySelectorAll('.bulk-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            bulkIds = JSON.parse(btn.dataset.ids);
            document.getElementById('bulkCount').textContent = btn.dataset.count;
            document.getElementById('bulkCategory').textContent = btn.dataset.category || '';
            var icon = document.getElementById('bulkIcon');
            if (icon) icon.className = 'bi bi-check2-all me-1';
            var confirmBtn = document.getElementById('bulkConfirm');
            confirmBtn.disabled = false;
            document.getElementById('bulkSpinner').classList.add('d-none');
            bulkModal.show();
        });
    });

    document.getElementById('bulkConfirm').addEventListener('click', function () {
        var spinner = document.getElementById('bulkSpinner');
        spinner.classList.remove('d-none');
        this.disabled = true;

        fetch('{{ route("checklist.bulk-complete") }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}'
            },
            body: JSON.stringify({ids: bulkIds})
        })
        .then(function (r) { return r.json(); })
        .then(function () { window.location.reload(); })
        .catch(function () { spinner.classList.add('d-none'); });
    });

    // ── Occurrence history modal ───────────────────────────────
    var historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    document.querySelectorAll('.history-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.id;
            document.getElementById('historyBody').innerHTML = '<div class="text-center py-3"><span class="spinner-border text-secondary"></span></div>';
            historyModal.show();
            fetch('/checklist/' + id + '/history', {
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var actionMap = {complete:'Concluída',reopen:'Reaberta',complete_bulk:'Concluída (lote)',complete_overdue:'Concluída (atrasada)'};
                var rows = data.logs.map(function (l) {
                    return '<tr><td>' + (actionMap[l.action] || l.action) + '</td><td>' + (l.user || '—') + '</td><td>' + (l.done_at || '—') + '</td><td class="text-muted">' + (l.justification || '') + '</td></tr>';
                }).join('');
                document.getElementById('historyTitle').textContent = data.activity;
                document.getElementById('historyBody').innerHTML = rows
                    ? '<table class="table table-sm"><thead><tr><th>Ação</th><th>Usuário</th><th>Hora</th><th>Justificativa</th></tr></thead><tbody>' + rows + '</tbody></table>'
                    : '<p class="text-muted">Sem histórico registrado.</p>';
            });
        });
    });

    // ── Persist accordion state ────────────────────────────────
    var storeKey = 'checklist_collapsed_{{ auth()->id() }}';
    var stored = {};
    try { stored = JSON.parse(localStorage.getItem(storeKey) || '{}'); } catch(e) {}

    document.querySelectorAll('.collapse').forEach(function (el) {
        if (stored[el.id] === false) { el.classList.remove('show'); }
        el.addEventListener('hidden.bs.collapse', function () { stored[el.id] = false; localStorage.setItem(storeKey, JSON.stringify(stored)); });
        el.addEventListener('shown.bs.collapse',  function () { delete stored[el.id]; localStorage.setItem(storeKey, JSON.stringify(stored)); });
    });
})();
</script>
@endpush
@endsection