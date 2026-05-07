@extends('layouts.app')
@section('title', 'Ordenar Categorias')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Ordenar Categorias</h4>
    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>
<p class="text-muted small">Arraste as categorias e subcategorias para reordenar. A ordem é salva automaticamente.</p>

<div id="sort-list" class="d-flex flex-column gap-2" style="max-width:480px">
    @foreach($categories as $cat)
    <div class="card border-0 shadow-sm" data-id="{{ $cat->id }}" data-type="category">
        <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
            <i class="bi bi-grip-vertical text-muted sort-handle" style="cursor:grab"></i>
            <span class="fw-semibold flex-grow-1">{{ $cat->name }}</span>
            <span class="badge bg-light text-muted border">{{ $cat->order }}</span>
        </div>
        @if($cat->subcategories->isNotEmpty())
        <div class="sub-list px-3 pb-2" data-parent="{{ $cat->id }}">
            @foreach($cat->subcategories as $sub)
            <div class="d-flex align-items-center gap-2 py-1 border-top sub-item" data-id="{{ $sub->id }}" data-type="subcategory">
                <i class="bi bi-grip-vertical text-muted sub-handle" style="cursor:grab;font-size:.8rem"></i>
                <span class="text-muted flex-grow-1" style="font-size:.9rem">{{ $sub->name }}</span>
                <span class="badge bg-light text-muted border" style="font-size:.65rem">{{ $sub->order }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach
</div>

<div id="sort-status" class="mt-3 text-muted small"></div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var statusEl = document.getElementById('sort-status');

    function buildPayload() {
        var items = [];
        document.querySelectorAll('#sort-list > [data-type=category]').forEach(function (el, i) {
            var subs = [];
            el.querySelectorAll('.sub-item').forEach(function (s, j) { subs.push({id: +s.dataset.id, order: j}); });
            items.push({id: +el.dataset.id, order: i, subcategories: subs});
        });
        return items;
    }

    function save() {
        statusEl.textContent = 'Salvando...';
        fetch('{{ route("categories.reorder") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({items: buildPayload()})
        })
        .then(function (r) { return r.json(); })
        .then(function () { statusEl.textContent = 'Salvo!'; setTimeout(function () { statusEl.textContent = ''; }, 2000); })
        .catch(function () { statusEl.textContent = 'Erro ao salvar.'; });
    }

    Sortable.create(document.getElementById('sort-list'), {
        handle: '.sort-handle',
        animation: 150,
        onEnd: save
    });

    document.querySelectorAll('.sub-list').forEach(function (list) {
        Sortable.create(list, {handle: '.sub-handle', animation: 150, onEnd: save});
    });
})();
</script>
@endpush
@endsection