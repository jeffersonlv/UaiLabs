@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Empresas</h4>
    <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">+ Nova Empresa</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body pb-0">
        <form method="GET" action="{{ route('admin.companies.index') }}">
            <div class="input-group input-group-sm" style="max-width:340px;">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou e-mail..." value="{{ $search }}">
                <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                @if($search)
                    <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-danger">✕</a>
                @endif
            </div>
        </form>
    </div>
    <table class="table table-hover mb-0 mt-2">
        <thead class="table-light">
            <tr>
                <th>Nome</th>
                <th>Slug</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Usuários</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($companies as $company)
            <tr>
                <td class="fw-semibold">{{ $company->name }}</td>
                <td class="text-muted small">{{ $company->slug }}</td>
                <td>{{ $company->email ?? '—' }}</td>
                <td>{{ $company->phone ?? '—' }}</td>
                <td>{{ $company->users_count }}</td>
                <td>
                    <span class="badge bg-{{ $company->active ? 'success' : 'secondary' }}">
                        {{ $company->active ? 'Ativa' : 'Inativa' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.units.index', $company) }}" class="btn btn-sm btn-outline-secondary" title="Gerenciar filiais">
                        <i class="bi bi-building"></i> Filiais
                    </a>
                    <a href="{{ route('admin.modules.index', $company) }}" class="btn btn-sm btn-outline-info" title="Gerenciar módulos">
                        <i class="bi bi-puzzle"></i> Módulos
                    </a>
                    <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                    <form method="POST" action="{{ route('admin.companies.toggle', $company) }}" class="d-inline">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-outline-{{ $company->active ? 'warning' : 'success' }}">
                            {{ $company->active ? 'Desativar' : 'Ativar' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" class="d-inline"
                          onsubmit="return confirm('Excluir empresa {{ $company->name }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Excluir</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-3">Nenhuma empresa encontrada.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($companies->hasPages())
    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $companies->firstItem() }}–{{ $companies->lastItem() }} de {{ $companies->total() }} registros</small>
        {{ $companies->links() }}
    </div>
    @endif
</div>
@endsection
