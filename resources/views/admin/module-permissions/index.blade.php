@extends('layouts.app')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.companies.index') }}" class="btn btn-sm btn-outline-secondary">← Empresas</a>
    <h4 class="mb-0">Módulos — {{ $company->name }}</h4>
</div>

{{-- Role permissions form --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-shield-check me-1"></i> Permissões por Papel
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Define quais módulos cada papel pode acessar nesta empresa. Alterações afetam todos os usuários
            do papel, exceto os que possuem configuração individual abaixo.
        </p>
        <form method="POST" action="{{ route('admin.modules.updateRole', $company) }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center mb-3">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start" style="min-width:200px">Módulo</th>
                            @foreach($roles as $role)
                                <th>{{ ucfirst($role) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $mod)
                            <tr>
                                <td class="text-start">
                                    <i class="bi {{ $mod['icon'] }} me-1"></i>
                                    {{ $mod['name'] }}
                                </td>
                                @foreach($roles as $role)
                                    @php
                                        $perm    = $rolePerms->get("{$mod['key']}.{$role}");
                                        $enabled = $perm ? $perm->enabled : true;
                                    @endphp
                                    <td>
                                        <div class="form-check form-switch d-flex justify-content-center m-0">
                                            <input type="hidden"
                                                   name="permissions[{{ $mod['key'] }}][{{ $role }}]"
                                                   value="0">
                                            <input class="form-check-input" type="checkbox"
                                                   name="permissions[{{ $mod['key'] }}][{{ $role }}]"
                                                   value="1"
                                                   role="switch"
                                                   {{ $enabled ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-save me-1"></i> Salvar permissões por papel
            </button>
        </form>
    </div>
</div>

{{-- Hidden forms for per-user saves (placed outside table for valid HTML) --}}
@foreach($users as $user)
    <form id="user-form-{{ $user->id }}"
          method="POST"
          action="{{ route('admin.modules.updateUser', $user) }}"
          class="d-none">
        @csrf
    </form>
@endforeach

{{-- Per-user overrides --}}
<div class="card">
    <div class="card-header fw-semibold">
        <i class="bi bi-person-gear me-1"></i> Permissões Individuais
    </div>
    @if($users->isEmpty())
        <div class="card-body text-muted">Nenhum usuário encontrado para esta empresa.</div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Usuário</th>
                        @foreach($modules as $mod)
                            <th class="text-center">
                                <i class="bi {{ $mod['icon'] }} me-1"></i>{{ $mod['name'] }}
                            </th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>
                                {{ $user->name }}
                                <span class="badge bg-secondary ms-1">{{ $user->role }}</span>
                            </td>
                            @foreach($modules as $mod)
                                @php
                                    $up  = $userPerms->get("{$user->id}.{$mod['key']}");
                                    $val = $up !== null ? ($up->enabled ? '1' : '0') : '';
                                @endphp
                                <td class="text-center">
                                    <select name="permissions[{{ $mod['key'] }}]"
                                            form="user-form-{{ $user->id }}"
                                            class="form-select form-select-sm mx-auto"
                                            style="width:auto;min-width:130px">
                                        <option value=""  {{ $val === ''  ? 'selected' : '' }}>Herdado do papel</option>
                                        <option value="1" {{ $val === '1' ? 'selected' : '' }}>Habilitado</option>
                                        <option value="0" {{ $val === '0' ? 'selected' : '' }}>Desabilitado</option>
                                    </select>
                                </td>
                            @endforeach
                            <td>
                                <button type="submit"
                                        form="user-form-{{ $user->id }}"
                                        class="btn btn-sm btn-outline-primary">
                                    Salvar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
