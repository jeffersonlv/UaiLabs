@extends('layouts.app')
@section('title', 'Atividades — Planilha')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Atividades — Edição em Planilha</h4>
    <div class="d-flex gap-2">
        <button id="btn-save" class="btn btn-primary btn-sm"><i class="bi bi-floppy me-1"></i>Salvar alterações</button>
        <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>
</div>
<div id="save-status" class="mb-2 small"></div>
<div id="spreadsheet" style="height:600px;overflow:auto;border:1px solid #dee2e6;border-radius:.375rem"></div>

@push('scripts')
{{-- Handsontable Community Edition --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@14.6.1/dist/handsontable.full.min.css">
<script src="https://cdn.jsdelivr.net/npm/handsontable@14.6.1/dist/handsontable.full.min.js"></script>
<script>
(function () {
    var categories = @json($categories->map(fn($c) => ['id'=>$c->id,'name'=>$c->name]));
    var units      = @json($units->map(fn($u) => ['id'=>$u->id,'name'=>$u->name]));
    var data       = @json($activities->map(fn($a) => [
        'id'           => $a->id,
        'title'        => $a->title,
        'description'  => $a->description,
        'category_id'  => $a->category_id,
        'periodicity'  => $a->periodicity,
        'active'       => $a->active,
    ]));

    var catNames = categories.map(function (c) { return c.name; });
    var periodicities = ['diario','semanal','quinzenal','mensal','bimestral','semestral','anual','pontual'];
    var catIdByName = {};
    categories.forEach(function (c) { catIdByName[c.name] = c.id; });

    var container = document.getElementById('spreadsheet');
    var hot = new Handsontable(container, {
        data: data.map(function (r) { return [r.id, r.title, r.description, categories.find(function(c){return c.id===r.category_id;})?.name || '', r.periodicity, r.active]; }),
        colHeaders: ['ID','Título','Descrição','Categoria','Periodicidade','Ativa'],
        columns: [
            {data: 0, readOnly: true, width: 40},
            {data: 1, width: 240},
            {data: 2, width: 200},
            {data: 3, type: 'dropdown', source: catNames, width: 160},
            {data: 4, type: 'dropdown', source: periodicities, width: 120},
            {data: 5, type: 'checkbox', width: 60},
        ],
        rowHeaders: true,
        stretchH: 'none',
        licenseKey: 'non-commercial-and-evaluation',
        height: '100%',
        contextMenu: ['row_above','row_below','remove_row','undo','redo'],
    });

    document.getElementById('btn-save').addEventListener('click', function () {
        var btn = this;
        btn.disabled = true;
        var rows = hot.getData().map(function (r) {
            return {
                id:           r[0] || null,
                title:        r[1],
                description:  r[2] || '',
                category_id:  catIdByName[r[3]] || null,
                periodicity:  r[4],
                active:       r[5] !== false,
            };
        }).filter(function (r) { return r.title; });

        fetch('{{ route("activities.bulk-save") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({rows: rows})
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            document.getElementById('save-status').innerHTML = '<span class="text-success">Salvo: ' + res.saved + ' registros.</span>' +
                (Object.keys(res.errors).length ? ' <span class="text-danger">Erros: ' + Object.keys(res.errors).length + '</span>' : '');
            btn.disabled = false;
        })
        .catch(function () {
            document.getElementById('save-status').innerHTML = '<span class="text-danger">Erro ao salvar.</span>';
            btn.disabled = false;
        });
    });
})();
</script>
@endpush
@endsection