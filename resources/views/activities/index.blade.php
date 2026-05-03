@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Atividades</h4>
    @if(!auth()->user()->isSuperAdmin())
        <a href="{{ route('activities.create') }}" class="btn btn-primary btn-sm">+ Nova atividade</a>
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
                            <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="input-group input-group-sm" style="max-width:300px;">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por título ou categoria..." value="{{ $search }}">
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
                <th>Categoria</th>
                <th>Unidade</th>
                <th>Periodicidade</th>
                <th class="text-center">Sequência</th>
                <th class="text-center">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $act)
            <tr>
                <td class="fw-medium">{{ $act->title }}</td>
                @if($companies)<td class="text-muted small">{{ $act->company?->name ?? '—' }}</td>@endif
                <td>{{ $act->category->name ?? '—' }}</td>
                <td>{{ $act->unit->name ?? 'Todas' }}</td>
                <td class="text-capitalize">{{ $act->periodicity }}</td>
                <td class="text-center">{{ $act->sequence_required ? 'Ordem '.$act->sequence_order : '—' }}</td>
                <td class="text-center">
                    <span class="badge bg-{{ $act->active ? 'success' : 'secondary' }}">{{ $act->active ? 'Ativa' : 'Inativa' }}</span>
                </td>
                <td class="text-end">
                    @if(!auth()->user()->isSuperAdmin())
                    <a href="{{ route('activities.edit', $act) }}" class="btn btn-sm btn-outline-secondary me-1">Editar</a>
                    <form method="POST" action="{{ route('activities.destroy', $act) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Desativar?')">Desativar</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ $companies ? 8 : 7 }}" class="text-muted text-center py-3">Nenhuma atividade encontrada.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($activities->hasPages())
    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $activities->firstItem() }}–{{ $activities->lastItem() }} de {{ $activities->total() }} registros</small>
        {{ $activities->links() }}
    </div>
    @endif
</div>
@endsection
