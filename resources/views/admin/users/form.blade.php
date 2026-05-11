@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ $user->exists ? 'Editar Usuário' : 'Novo Usuário' }}</h4>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if($user->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Usuário <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                           value="{{ old('username', $user->username) }}" required
                           placeholder="login fácil de digitar">
                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Senha {{ $user->exists ? '(deixe em branco para não alterar)' : '*' }}
                    </label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           {{ $user->exists ? '' : 'required' }}>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Confirmar Senha</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Perfil <span class="text-danger">*</span></label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">Selecione...</option>
                        <option value="superadmin" {{ old('role', $user->role) === 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="admin"      {{ old('role', $user->role) === 'admin'      ? 'selected' : '' }}>Admin</option>
                        <option value="manager"    {{ old('role', $user->role) === 'manager'    ? 'selected' : '' }}>Gerente</option>
                        <option value="staff"      {{ old('role', $user->role) === 'staff'      ? 'selected' : '' }}>Staff</option>
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Empresa</label>
                    <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror">
                        <option value="">— Nenhuma (Super Admin) —</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}"
                            {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Filiais / Unidades</label>
                    <div id="units-container" class="d-flex flex-wrap gap-3 mt-1">
                        <span class="text-muted small">Selecione uma empresa para ver as unidades.</span>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                               {{ old('active', $user->active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Acesso habilitado</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    {{ $user->exists ? 'Salvar Alterações' : 'Criar Usuário' }}
                </button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
const unitsByCompany = @json($unitsByCompany);
const selectedIds    = (@json($user->exists ? $user->units->pluck('id') : collect())).map(Number);
const typeLabels     = { matriz: 'Matriz', filial: 'Filial', quiosque: 'Quiosque', dark_kitchen: 'Dark Kitchen' };

function updateUnits() {
    const companyId  = document.getElementById('company_id').value;
    const container  = document.getElementById('units-container');
    const units      = unitsByCompany[companyId] || [];

    if (!companyId || units.length === 0) {
        container.innerHTML = '<span class="text-muted small">' +
            (companyId ? 'Nenhuma unidade cadastrada para esta empresa.' : 'Selecione uma empresa para ver as unidades.') +
            '</span>';
        return;
    }

    container.innerHTML = units.map(u => `
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="unit_ids[]"
                   id="unit-${u.id}" value="${u.id}"
                   ${selectedIds.includes(Number(u.id)) ? 'checked' : ''}>
            <label class="form-check-label" for="unit-${u.id}">
                ${u.name}
                <span class="badge bg-secondary ms-1" style="font-size:.7rem">${typeLabels[u.type] ?? u.type}</span>
            </label>
        </div>
    `).join('');
}

document.getElementById('company_id').addEventListener('change', updateUnits);
updateUnits();
</script>
@endpush
@endsection
