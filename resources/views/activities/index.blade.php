@extends('layouts.app')
@section('content')
@php $multiUnit = !$companies && $units->count() >= 2; @endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Atividades</h4>
    @if(!auth()->user()->isSuperAdmin())
        <div class="d-flex gap-2">
            <a href="{{ route('activities.spreadsheet') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-table me-1"></i>Planilha</a>
            <a href="{{ route('activities.create') }}" class="btn btn-primary btn-sm">+ Nova atividade</a>
        </div>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body pb-0">
        <form method="GET" action="{{ route('activities.index') }}">
            <div class="d-flex flex-wrap gap-2 align-items-end">
                @if($companies)
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Empresa</label>
                    <select name="company_id" class="form-select form-select-sm" style="min-width:200px;" onchange="this.form.submit()">
                        <option value="">— Todas as empresas —</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="input-group input-group-sm" style="max-width:300px;">
                    <input type="text" name="search" class="form-control" placeholder="Buscar título ou categoria..." value="{{ $search }}">
                    @if($companies && $selectedCompanyId)
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    @endif
                    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                    @if($search)
                        <a href="{{ route('activities.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="btn btn-outline-danger">✕</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
    <table class="table table-hover mb-0 mt-2">
        <thead class="table-light">
            <tr>
                <th>Título</th>
                @if($companies)<th>Empresa</th>@endif
                <th>Categoria / Subcategoria</th>
                @if($multiUnit)<th>Unidade(s)</th>@endif
                <th>Periodicidade</th>
                <th class="text-center">Seq.</th>
                <th class="text-center">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $act)
            <tr>
                <td class="fw-medium">{{ $act->title }}</td>
                @if($companies)<td class="text-muted small">{{ $act->company?->name ?? '—' }}</td>@endif
                <td>
                    <span class="fw-medium">{{ $act->category->name ?? '—' }}</span>
                    @if($act->subcategory)
                        <br><span class="text-muted" style="font-size:.75rem">{{ $act->subcategory->name }}</span>
                    @endif
                </td>
                @if($multiUnit)
                <td>
                    {{-- Badges com unidades atuais --}}
                    <div class="unit-badges mb-1" data-id="{{ $act->id }}">
                        @if($act->units->isEmpty())
                            <span class="badge bg-secondary" style="font-size:.65rem">Geral</span>
                        @else
                            @foreach($act->units as $unit)
                                <span class="badge bg-light text-secondary border me-1" style="font-size:.65rem">{{ $unit->name }}</span>
                            @endforeach
                        @endif
                    </div>
                    {{-- Botões de ação --}}
                    <div class="d-flex gap-1 flex-wrap">
                        <button type="button"
                                class="btn btn-xs btn-outline-secondary assign-geral"
                                style="font-size:.7rem;padding:.1rem .45rem"
                                data-id="{{ $act->id }}"
                                data-url="{{ route('activities.assign-units', $act) }}">
                            Geral
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-xs btn-outline-primary dropdown-toggle"
                                    style="font-size:.7rem;padding:.1rem .45rem"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Unidades
                            </button>
                            <div class="dropdown-menu p-2" style="min-width:180px"
                                 data-id="{{ $act->id }}"
                                 data-url="{{ route('activities.assign-units', $act) }}">
                                @foreach($units as $unit)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input unit-chk"
                                           id="u_{{ $act->id }}_{{ $unit->id }}"
                                           value="{{ $unit->id }}"
                                           {{ $act->units->contains($unit->id) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="u_{{ $act->id }}_{{ $unit->id }}" style="font-size:.8rem">
                                        {{ $unit->name }}
                                    </label>
                                </div>
                                @endforeach
                                <button type="button" class="btn btn-sm btn-primary w-100 mt-2 save-units">Salvar</button>
                            </div>
                        </div>
                    </div>
                </td>
                @endif
                <td class="text-capitalize">{{ $act->periodicity }}</td>
                <td class="text-center small">{{ $act->sequence_required ? $act->sequence_order : '—' }}</td>
                <td class="text-center">
                    <span class="badge bg-{{ $act->active ? 'success' : 'secondary' }}">{{ $act->active ? 'Ativa' : 'Inativa' }}</span>
                </td>
                <td class="text-end">
                    @if(!auth()->user()->isSuperAdmin())
                    <a href="{{ route('activities.edit', $act) }}" class="btn btn-sm btn-outline-secondary me-1">Editar</a>
                    @if($act->active)
                        <form method="POST" action="{{ route('activities.destroy', $act) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Desativar esta atividade?')">Desativar</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('activities.update', $act) }}" class="d-inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="title" value="{{ $act->title }}">
                            <input type="hidden" name="category_id" value="{{ $act->category_id }}">
                            <input type="hidden" name="periodicity" value="{{ $act->periodicity }}">
                            <input type="hidden" name="active" value="1">
                            <button class="btn btn-sm btn-outline-success" onclick="return confirm('Reativar esta atividade?')">Reativar</button>
                        </form>
                    @endif
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ $companies ? ($multiUnit ? 9 : 8) : ($multiUnit ? 8 : 7) }}" class="text-muted text-center py-3">Nenhuma atividade encontrada.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($activities->hasPages())
    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $activities->firstItem() }}–{{ $activities->lastItem() }} de {{ $activities->total() }}</small>
        {{ $activities->links() }}
    </div>
    @endif
</div>

@if($multiUnit)
@push('scripts')
<script>
var csrf = '{{ csrf_token() }}';

function updateBadges(actId, units) {
    var el = document.querySelector('.unit-badges[data-id="' + actId + '"]');
    if (!el) return;
    if (!units || units.length === 0) {
        el.innerHTML = '<span class="badge bg-secondary" style="font-size:.65rem">Geral</span>';
    } else {
        el.innerHTML = units.map(function (n) {
            return '<span class="badge bg-light text-secondary border me-1" style="font-size:.65rem">' + n + '</span>';
        }).join('');
    }
}

// Botão Geral
document.querySelectorAll('.assign-geral').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var id = btn.dataset.id;
        var url = btn.dataset.url;
        btn.disabled = true;
        fetch(url, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ unit_ids: [] })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            updateBadges(id, data.units);
            // desmarcar todos os checkboxes deste dropdown
            document.querySelectorAll('.unit-chk[id^="u_' + id + '_"]').forEach(function (c) { c.checked = false; });
        })
        .finally(function () { btn.disabled = false; });
    });
});

// Botão Salvar dentro do dropdown
document.querySelectorAll('.save-units').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var menu = btn.closest('.dropdown-menu');
        var id   = menu.dataset.id;
        var url  = menu.dataset.url;
        var ids  = Array.from(menu.querySelectorAll('.unit-chk:checked')).map(function (c) { return parseInt(c.value); });
        btn.disabled = true;
        fetch(url, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ unit_ids: ids })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            updateBadges(id, data.units);
            bootstrap.Dropdown.getInstance(menu.previousElementSibling)?.hide();
        })
        .finally(function () { btn.disabled = false; });
    });
});
</script>
@endpush
@endif
@endsection
