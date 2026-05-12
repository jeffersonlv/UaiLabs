@extends('layouts.app')
@section('title', 'Turno')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Detalhes do Turno</h4>
    <div class="d-flex gap-2">
        @if(auth()->user()->isManagerOrAbove())
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                <i class="bi bi-pencil me-1"></i>Editar
            </button>
            <form method="POST" action="{{ route('shifts.destroy', $shift) }}"
                  onsubmit="return confirm('Excluir turno de {{ addslashes($shift->user->name) }}?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Excluir</button>
            </form>
        @endif
        <a href="{{ route('shifts.calendar', ['unit_id' => $shift->unit_id]) }}"
           class="btn btn-sm btn-outline-secondary">Voltar</a>
    </div>
</div>

<div class="card border-0 shadow-sm p-4" style="max-width:480px">
    <dl class="row mb-0">
        <dt class="col-5">Funcionário</dt>
        <dd class="col-7">{{ $shift->user->name }}</dd>
        <dt class="col-5">Unidade</dt>
        <dd class="col-7">{{ $shift->unit?->name ?? '—' }}</dd>
        <dt class="col-5">Tipo</dt>
        <dd class="col-7"><span class="badge bg-{{ $shift->typeColor() }}">{{ $shift->typeLabel() }}</span></dd>
        <dt class="col-5">Início</dt>
        <dd class="col-7">{{ $shift->start_at->format('d/m/Y H:i') }}</dd>
        <dt class="col-5">Fim</dt>
        <dd class="col-7">{{ $shift->end_at->format('d/m/Y H:i') }}</dd>
        <dt class="col-5">Duração</dt>
        <dd class="col-7">{{ \App\Services\TimeCalculationService::formatMinutes($shift->durationMinutes()) }}</dd>
        @if($shift->notes)
        <dt class="col-5">Notas</dt>
        <dd class="col-7 text-muted">{{ $shift->notes }}</dd>
        @endif
        <dt class="col-5">Criado por</dt>
        <dd class="col-7 text-muted">{{ $shift->creator?->name ?? '—' }} em {{ $shift->created_at->format('d/m/Y') }}</dd>
    </dl>
</div>

@if(auth()->user()->isManagerOrAbove())
{{-- Modal: Editar turno --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editForm" method="POST" action="{{ route('shifts.update', $shift) }}">
            @csrf @method('PUT')
            <input type="hidden" name="unit_id" value="{{ $shift->unit_id }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Editar turno</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Funcionário</label>
                        <input class="form-control form-control-sm" value="{{ $shift->user->name }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Data <span class="text-danger">*</span></label>
                        <input type="date" id="eDate" class="form-control form-control-sm" required
                               value="{{ $shift->start_at->toDateString() }}">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label form-label-sm">Entrada <span class="text-danger">*</span></label>
                            <input type="time" id="eStart" class="form-control form-control-sm" required
                                   value="{{ $shift->start_at->format('H:i') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm">Saída <span class="text-danger">*</span></label>
                            <input type="time" id="eEnd" class="form-control form-control-sm" required
                                   value="{{ $shift->end_at->format('H:i') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Tipo</label>
                        <select name="type" class="form-select form-select-sm">
                            @foreach(\App\Models\Shift::TYPES as $k => $meta)
                            <option value="{{ $k }}" {{ $shift->type === $k ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label form-label-sm">Notas</label>
                        <input type="text" name="notes" class="form-control form-control-sm"
                               value="{{ $shift->notes }}" maxlength="500">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('editForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var date = document.getElementById('eDate').value;
    fetch(this.action, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            unit_id:  this.querySelector('[name=unit_id]').value,
            user_id:  {{ $shift->user_id }},
            start_at: date + ' ' + document.getElementById('eStart').value + ':00',
            end_at:   date + ' ' + document.getElementById('eEnd').value   + ':00',
            type:     this.querySelector('[name=type]').value,
            notes:    this.querySelector('[name=notes]').value,
        }),
    })
    .then(function (r) { return r.json(); })
    .then(function (d) { if (d.ok) window.location.reload(); else alert(d.message || 'Erro'); });
});
</script>
@endpush
@endif
@endsection
