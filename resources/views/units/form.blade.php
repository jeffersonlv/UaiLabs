@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ $unit->exists ? 'Editar Unidade' : 'Nova Unidade' }}</h4>
    <a href="{{ route('units.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ $unit->exists ? route('units.update', $unit) : route('units.store') }}">
            @csrf
            @if($unit->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $unit->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        @foreach(\App\Models\Unit::TYPES as $value => $label)
                        <option value="{{ $value }}" {{ old('type', $unit->type) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Endereço</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                           value="{{ old('address', $unit->address) }}" placeholder="Rua, número, bairro...">
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @if($unit->exists)
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                               {{ old('active', $unit->active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Unidade ativa</label>
                    </div>
                </div>
                @endif
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    {{ $unit->exists ? 'Salvar Alterações' : 'Criar Unidade' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
