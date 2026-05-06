@extends('layouts.app')
@section('content')
<div style="max-width:560px">
    <h4 class="mb-4">{{ $activity->id ? 'Editar' : 'Nova' }} atividade</h4>
    <div class="card border-0 shadow-sm p-4">
        <form method="POST" action="{{ $activity->id ? route('activities.update', $activity) : route('activities.store') }}">
            @csrf
            @if($activity->id) @method('PUT') @endif
            <div class="mb-3">
                <label class="form-label">Título</label>
                <input name="title" value="{{ old('title', $activity->title) }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" rows="2" class="form-control">{{ old('description', $activity->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Categoria</label>
                <select name="category_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $activity->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Unidade</label>
                <select name="unit_id" class="form-select">
                    <option value="">Geral — aparece para todas as unidades</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id', $activity->unit_id) == $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }} — {{ $unit->typeLabel() }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">
                    Selecione uma unidade específica se esta atividade for exclusiva dela.
                    Deixe "Geral" para que apareça em todas.
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Periodicidade</label>
                <select name="periodicity" class="form-select" required>
                    @foreach(['diario','semanal','quinzenal','mensal','bimestral','semestral','anual','pontual'] as $p)
                        <option value="{{ $p }}" {{ old('periodicity', $activity->periodicity) == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="sequence_required" id="seq" class="form-check-input" value="1"
                    {{ old('sequence_required', $activity->sequence_required) ? 'checked' : '' }}
                    onchange="document.getElementById('seq_order').classList.toggle('d-none', !this.checked)">
                <label for="seq" class="form-check-label">Faz parte de uma sequência obrigatória</label>
            </div>
            <div id="seq_order" class="mb-3 {{ old('sequence_required', $activity->sequence_required) ? '' : 'd-none' }}">
                <label class="form-label">Ordem na sequência</label>
                <input type="number" name="sequence_order" value="{{ old('sequence_order', $activity->sequence_order) }}" min="1" class="form-control" style="width:100px">
            </div>
            @if($activity->id)
            <div class="mb-3 form-check">
                <input type="checkbox" name="active" id="active" class="form-check-input" value="1" {{ $activity->active ? 'checked' : '' }}>
                <label for="active" class="form-check-label">Ativa</label>
            </div>
            @endif
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
