@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Usuários</h4>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">+ Novo Usuário</a>
</div>

@php
$roleBadge = ['superadmin' => 'danger', 'admin' => 'primary', 'manager' => 'info', 'staff' => 'secondary'];
$roleLabel  = ['superadmin' => 'Super Admin', 'admin' => 'Admin', 'manager' => 'Gerente', 'staff' => 'Staff'];
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-body pb-0">
        <form method="GET" action="{{ route('admin.users.index') }}">
            <div class="input-group input-group-sm" style="max-width:340px;">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou e-mail..." value="{{ $search }}">
                <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                @if($search)
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-danger">✕</a>
                @endif
            </div>
        </form>
    </div>
    <table class="table table-hover mb-0 mt-2">
        <thead class="table-light">
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Empresa</th>
                <th>Acesso</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td class="fw-semibold">{{ $user->name }}</td>
                <td class="text-muted small">{{ $user->email }}</td>
                <td>
                    <span class="badge bg-{{ $roleBadge[$user->role] ?? 'secondary' }}">
                        {{ $roleLabel[$user->role] ?? $user->role }}
                    </span>
                </td>
                <td>{{ $user->company?->name ?? '—' }}</td>
                <td>
                    <span class="badge bg-{{ $user->active ? 'success' : 'danger' }}">
                        {{ $user->active ? 'Ativo' : 'Inativo' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                    @if(!$user->isSuperAdmin())
                    <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="d-inline">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-outline-{{ $user->active ? 'warning' : 'success' }}">
                            {{ $user->active ? 'Desativar' : 'Ativar' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                          onsubmit="return confirm('Excluir usuário {{ $user->name }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Excluir</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-3">Nenhum usuário encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())
    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $users->firstItem() }}–{{ $users->lastItem() }} de {{ $users->total() }} registros</small>
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
