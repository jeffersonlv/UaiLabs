@extends('layouts.app')
@section('content')
<div style="max-width:420px">
    <h4 class="mb-4">{{ $schedule->id ? 'Editar' : 'Novo' }} tipo de turno</h4>
    <div class="card border-0 shadow-sm p-4">
        <form method="POST" action="{{ $schedule->id ? route('work-schedules.update', $schedule) : route('work-schedules.store') }}">
            @csrf @if($schedule->id) @method('PUT') @endif
            <div class="mb-3">
                <label class="form-label">Nome <span class="text-danger">*</span></label>
                <input name="name" value="{{ old('name', $schedule->name) }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Horas semanais <span class="text-danger">*</span></label>
                <input type="number" name="weekly_hours" value="{{ old('weekly_hours', $schedule->weekly_hours ?? 40) }}"
                       step="0.5" min="1" max="168" class="form-control" required style="width:120px">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_default" id="is_default" class="form-check-input" value="1"
                       {{ old('is_default', $schedule->is_default ?? false) ? 'checked' : '' }}>
                <label for="is_default" class="form-check-label">Definir como padrão</label>
            </div>
            @if($schedule->id)
            <div class="mb-3 form-check">
                <input type="checkbox" name="active" id="active" class="form-check-input" value="1" {{ $schedule->active ? 'checked' : '' }}>
                <label for="active" class="form-check-label">Ativo</label>
            </div>
            @endif
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('work-schedules.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection