@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ $company->exists ? 'Editar Empresa' : 'Nova Empresa' }}</h4>
    <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ $company->exists ? route('admin.companies.update', $company) : route('admin.companies.store') }}">
            @csrf
            @if($company->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $company->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                           value="{{ old('slug', $company->slug) }}" required>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">E-mail</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $company->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Telefone</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $company->phone) }}">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                               {{ old('active', $company->active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Empresa ativa</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    {{ $company->exists ? 'Salvar Alterações' : 'Criar Empresa' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('name').addEventListener('input', function () {
    const slug = this.value.toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    const slugField = document.getElementById('slug');
    if (!slugField.dataset.edited) slugField.value = slug;
});
document.getElementById('slug').addEventListener('input', function () {
    this.dataset.edited = '1';
});
</script>
@endsection
