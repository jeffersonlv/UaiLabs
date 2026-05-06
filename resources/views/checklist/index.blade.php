@extends('layouts.app')
@section('content')
<div class="d-flex align-items-baseline justify-content-between mb-1">
    <h4 class="mb-0">Checklist — {{ now()->format('d/m/Y') }}</h4>
    <span class="text-muted small">Olá, {{ auth()->user()->name }}</span>
</div>
@if($visibleUnits->count() === 1)
    <p class="text-muted mb-4">
        <i class="bi bi-building me-1"></i>
        <strong>{{ $visibleUnits->first()->name }}</strong>
        <span class="badge bg-secondary ms-1" style="font-size:.65rem">{{ $visibleUnits->first()->typeLabel() }}</span>
    </p>
@else
    <p class="text-muted mb-4">{{ $visibleUnits->pluck('name')->join(' · ') }}</p>
@endif

@php
    $byUnit = $occurrences->groupBy(fn($o) => $o->activity->unit->name ?? 'Sem unidade');
    $multiUnit = $byUnit->count() > 1;
@endphp

@forelse($byUnit as $unitName => $unitOccurrences)

@if($multiUnit)
    @php $unit = $visibleUnits->firstWhere('name', $unitName); @endphp
    <div class="d-flex align-items-center gap-2 mb-2 mt-3">
        <i class="bi bi-building text-primary"></i>
        <span class="fw-semibold text-primary">{{ $unitName }}</span>
        @if($unit)
            <span class="badge bg-secondary" style="font-size:.65rem">{{ $unit->typeLabel() }}</span>
        @endif
    </div>
@endif

@foreach($unitOccurrences->groupBy(fn($o) => $o->activity->category->name ?? 'Sem categoria') as $category => $items)
<h6 class="text-uppercase text-muted small mb-2 {{ $multiUnit ? 'ms-3' : '' }}">{{ $category }}</h6>
<div class="card border-0 shadow-sm mb-4">
    @foreach($items as $occ)
    @php
        $act  = $occ->activity;
        $done = in_array($occ->status, ['DONE', 'REOPENED']);
    @endphp
    <div class="d-flex align-items-start gap-3 p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 mb-1">
                @if($act->sequence_required)
                    <span class="badge text-white" style="background:#6f42c1!important">Seq. {{ $act->sequence_order }}</span>
                @endif
                <span class="fw-medium {{ $done ? 'text-decoration-line-through text-muted' : '' }}">{{ $act->title }}</span>
            </div>
            @if($act->description)
                <small class="text-muted">{{ $act->description }}</small>
            @endif
            @if($occ->logs->isNotEmpty())
                <div class="mt-2 occurrence-history">
                    @foreach($occ->logs as $log)
                        <div class="d-flex align-items-baseline gap-1 small {{ $loop->last ? '' : 'mb-1' }}">
                            @if($log->action === 'complete')
                                <span class="text-success fw-semibold" style="font-size:.7rem">✓</span>
                                <span class="text-muted">
                                    Concluída por <span class="text-dark">{{ $log->user->name ?? '—' }}</span>
                                    às {{ $log->done_at->format('H:i') }}
                                </span>
                            @else
                                <span class="text-warning fw-semibold" style="font-size:.7rem">↺</span>
                                <span class="text-muted">
                                    Reexecutada por <span class="text-dark">{{ $log->user->name ?? '—' }}</span>
                                    às {{ $log->done_at->format('H:i') }}
                                    @if($log->justification)
                                        <span class="text-warning-emphasis">— "{{ $log->justification }}"</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="d-flex flex-column align-items-end gap-2" style="min-width:164px">
            <span class="badge bg-{{ $occ->status === 'DONE' || $occ->status === 'REOPENED' ? 'success' : ($occ->status === 'OVERDUE' ? 'danger' : 'warning text-dark') }}">
                {{ ['PENDING'=>'Pendente','DONE'=>'Concluída','OVERDUE'=>'Atrasada','REOPENED'=>'Reaberta'][$occ->status] }}
            </span>

            @if($done)
                {{-- Slider no estado concluído + Reexecutar expansível --}}
                <div class="stc stc-done" style="width:164px">
                    <div class="stc-track">
                        <span class="stc-label stc-label-done">Concluída ✓</span>
                        <div class="stc-handle stc-handle-done">✓</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('checklist.complete', $occ) }}" class="mt-1">
                    @csrf @method('PATCH')
                    <div class="reexec-wrap" style="width:164px">
                        <button type="button" class="btn btn-sm btn-outline-warning w-100 reexec-toggle">
                            Reexecutar
                        </button>
                        <div class="reexec-form mt-1" style="display:none">
                            <input name="justification" placeholder="Justificativa obrigatória"
                                   class="form-control form-control-sm mb-1" required>
                            <button type="submit" class="btn btn-sm btn-warning w-100">Confirmar</button>
                        </div>
                    </div>
                </form>
            @else
                {{-- Slide to confirm --}}
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

@empty
<p class="text-muted">Nenhuma tarefa para hoje.</p>
@endforelse

@once
@push('scripts')
<style>
.stc-track {
    position: relative;
    height: 34px;
    background: #d1e7dd;
    border-radius: 17px;
    overflow: hidden;
    user-select: none;
    touch-action: none;
    cursor: grab;
}
.stc-track:active { cursor: grabbing; }

.stc-label {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    color: #146c43;
    pointer-events: none;
    padding-left: 32px;
    transition: opacity .15s;
    white-space: nowrap;
}

.stc-handle {
    position: absolute;
    left: 3px;
    top: 3px;
    width: 28px;
    height: 28px;
    background: #198754;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
    line-height: 1;
    pointer-events: none;
    will-change: left;
}

.stc-submitting .stc-track {
    background: #198754;
    cursor: default;
}
.stc-submitting .stc-handle {
    background: #fff;
    color: #198754;
    font-size: .9rem;
}

/* Estado: já concluída */
.stc-done .stc-track {
    background: #198754;
    cursor: default;
    opacity: .85;
}
.stc-label-done {
    color: #fff !important;
    padding-left: 32px;
    font-weight: 500;
}
.stc-handle-done {
    left: calc(100% - 31px) !important;
    background: #fff !important;
    color: #198754 !important;
    font-size: .9rem !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.stc').forEach(function (widget) {
        var track   = widget.querySelector('.stc-track');
        var handle  = widget.querySelector('.stc-handle');
        var label   = widget.querySelector('.stc-label');
        var form    = widget.closest('form');

        var dragging = false;
        var startClientX = 0;
        var currentX = 0;

        function maxX() {
            return track.offsetWidth - handle.offsetWidth - 6; // 3px padding × 2
        }

        function clientX(e) {
            return e.touches ? e.touches[0].clientX : e.clientX;
        }

        function setPos(x, animate) {
            handle.style.transition = animate ? 'left .2s ease' : 'none';
            handle.style.left = (3 + x) + 'px';
            label.style.opacity = Math.max(0, 1 - x / maxX());
        }

        function onStart(e) {
            if (widget.classList.contains('stc-submitting')) return;
            dragging    = true;
            startClientX = clientX(e) - currentX;
            e.preventDefault();
        }

        function onMove(e) {
            if (!dragging) return;
            var x = Math.max(0, Math.min(clientX(e) - startClientX, maxX()));
            currentX = x;
            setPos(x, false);
            e.preventDefault();
        }

        function onEnd() {
            if (!dragging) return;
            dragging = false;

            if (currentX >= maxX() * 0.82) {
                // ── Confirmado: anima até o fim e envia ──
                currentX = maxX();
                setPos(currentX, true);
                label.style.opacity = 0;
                widget.classList.add('stc-submitting');
                handle.innerHTML = '✓';
                setTimeout(function () { form.submit(); }, 280);
            } else {
                // ── Não chegou: snap back ──
                currentX = 0;
                setPos(0, true);
            }
        }

        track.addEventListener('mousedown',  onStart);
        track.addEventListener('touchstart', onStart, { passive: false });
        document.addEventListener('mousemove',  onMove);
        document.addEventListener('touchmove',  onMove, { passive: false });
        document.addEventListener('mouseup',  onEnd);
        document.addEventListener('touchend', onEnd);
    });

    // Toggle Reexecutar
    document.querySelectorAll('.reexec-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.nextElementSibling;
            var open = form.style.display !== 'none';
            form.style.display = open ? 'none' : 'block';
            btn.classList.toggle('btn-warning',       !open);
            btn.classList.toggle('btn-outline-warning', open);
            if (!open) form.querySelector('input').focus();
        });
    });
});
</script>
@endpush
@endonce

@endsection
