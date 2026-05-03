@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Categorias</h4>
    @if(!auth()->user()->isSuperAdmin())
        <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">+ Nova categoria</a>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body pb-0">
        <form method="GET" action="{{ route('categories.index') }}">
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
                    <input type="text" name="search" class="form-control" placeholder="Buscar categoria..." value="{{ $search }}">
                    @if($companies && $selectedCompanyId)
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    @endif
                    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                    @if($search)
                        <a href="{{ route('categories.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="btn btn-outline-danger">✕</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
    <table class="table table-hover mb-0 mt-2">
        <thead class="table-light">
            <tr>
                <th>Nome</th>
                @if($companies)<th>Empresa</th>@endif
                <th>Descrição</th>
                <th class="text-center">Atividades</th>
                <th class="text-center">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $cat)
            <tr>
                <td class="fw-medium">{{ $cat->name }}</td>
                @if($companies)<td class="text-muted small">{{ $cat->company?->name ?? '—' }}</td>@endif
                <td class="text-muted">{{ $cat->description ?? '—' }}</td>
                <td class="text-center">{{ $cat->activities_count }}</td>
                <td class="text-center">
                    <span class="badge bg-{{ $cat->active ? 'success' : 'secondary' }}">{{ $cat->active ? 'Ativa' : 'Inativa' }}</span>
                </td>
                <td class="text-end">
                    @if(!auth()->user()->isSuperAdmin())
                    <a href="{{ route('categories.edit', $cat) }}" class="btn btn-sm btn-outline-secondary me-1">Editar</a>
                    <form method="POST" action="{{ route('categories.destroy', $cat) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Desativar?')">Desativar</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ $companies ? 6 : 5 }}" class="text-muted text-center py-3">Nenhuma categoria encontrada.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($categories->hasPages())
    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $categories->firstItem() }}–{{ $categories->lastItem() }} de {{ $categories->total() }} registros</small>
        {{ $categories->links() }}
    </div>
    @endif
</div>
@endsection
