@extends('layouts.app')
@section('content')
<div style="max-width:600px">
    <h4 class="mb-4">{{ $activity->id ? 'Editar' : 'Nova' }} atividade</h4>
    <div class="card border-0 shadow-sm p-4">
        <form method="POST" action="{{ $activity->id ? route('activities.update', $activity) : route('activities.store') }}">
            @csrf
            @if($activity->id) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label">Título <span class="text-danger">*</span></label>
                <input name="title" value="{{ old('title', $activity->title) }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" rows="2" class="form-control">{{ old('description', $activity->description) }}</textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Categoria <span class="text-danger">*</span></label>
                    <select name="category_id" id="category_id" class="form-select" required onchange="filterSubcats()">
                        <option value="">Selecione...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $activity->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subcategoria</label>
                    <select name="subcategory_id" id="subcategory_id" class="form-select">
                        <option value="">Nenhuma</option>
                        @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}"
                                    data-category="{{ $sub->category_id }}"
                                    {{ old('subcategory_id', $activity->subcategory_id) == $sub->id ? 'selected' : '' }}>
                                {{ $sub->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- N:N Unit assignment — só exibido se 2+ unidades --}}
            @if($units->count() >= 2)
            <div class="mb-3">
                <label class="form-label fw-semibold">Unidades</label>
                <div class="form-check mb-1">
                    <input type="checkbox" id="all_units" name="all_units" class="form-check-input" value="1"
                           {{ old('all_units') ? 'checked' : '' }}
                           onchange="document.getElementById('units-list').classList.toggle('d-none', this.checked)">
                    <label for="all_units" class="form-check-label">Aplicar a <strong>todas</strong> as unidades ativas</label>
                </div>
                <div id="units-list" class="{{ old('all_units') ? 'd-none' : '' }}">
                    @php $selectedUnitIds = old('unit_ids', $activity->id ? $activity->units->pluck('id')->toArray() : []); @endphp
                    @if($units->isEmpty())
                        <p class="text-muted small">Nenhuma unidade cadastrada.</p>
                    @else
                        @foreach($units as $unit)
                            <div class="form-check">
                                <input type="checkbox" name="unit_ids[]" id="unit_{{ $unit->id }}"
                                       class="form-check-input" value="{{ $unit->id }}"
                                       {{ in_array($unit->id, $selectedUnitIds) ? 'checked' : '' }}>
                                <label for="unit_{{ $unit->id }}" class="form-check-label">
                                    {{ $unit->name }}
                                    <span class="badge bg-light text-secondary border ms-1" style="font-size:.65rem">{{ $unit->typeLabel() }}</span>
                                </label>
                            </div>
                        @endforeach
                        <div class="form-text">Deixe todos desmarcados para "Geral" (visível apenas para admin).</div>
                    @endif
                </div>
            </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Periodicidade <span class="text-danger">*</span></label>
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
@push('scripts')
<script>
function filterSubcats() {
    var catId = document.getElementById('category_id').value;
    document.querySelectorAll('#subcategory_id option[data-category]').forEach(function (opt) {
        opt.hidden = catId && opt.dataset.category != catId;
    });
    var sel = document.getElementById('subcategory_id');
    if (sel.options[sel.selectedIndex]?.hidden) sel.value = '';
}
filterSubcats();
</script>
@endpush
@endsection