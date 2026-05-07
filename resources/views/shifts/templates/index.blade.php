@extends('layouts.app')
@section('title', 'Templates de Escala')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Templates de Escala</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTemplateModal">+ Novo template</button>
        <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Nome</th><th>Unidade</th><th>Período</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($templates as $tpl)
            <tr>
                <td class="fw-medium">{{ $tpl->name }}</td>
                <td>{{ $tpl->unit?->name ?? '—' }}</td>
                <td class="text-capitalize">{{ $tpl->period }}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#applyModal" data-id="{{ $tpl->id }}" data-name="{{ $tpl->name }}">
                        Aplicar
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-muted text-center py-3">Nenhum template criado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal: Novo template --}}
<div class="modal fade" id="newTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Novo Template</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('shifts.templates.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unidade</label>
                        <select name="unit_id" class="form-select" required>
                            @foreach($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Período</label>
                        <select name="period" class="form-select">
                            <option value="weekly">Semanal</option>
                            <option value="biweekly">Quinzenal</option>
                            <option value="monthly">Mensal</option>
                        </select>
                    </div>
                    <input type="hidden" name="config" value="[]">
                    <div class="alert alert-info small">Configure os turnos do template diretamente pelo JSON após criar.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Aplicar template --}}
<div class="modal fade" id="applyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="applyTitle">Aplicar Template</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="applyForm" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label">Data de início da aplicação</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Aplicar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('applyModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('applyTitle').textContent = 'Aplicar: ' + btn.dataset.name;
    document.getElementById('applyForm').action = '/shifts/templates/' + btn.dataset.id + '/apply';
});
</script>
@endpush
@endsection