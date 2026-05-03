@extends('layouts.app')
@section('content')
<div style="max-width:500px">
    <h4 class="mb-4">{{ $category->id ? 'Editar' : 'Nova' }} categoria</h4>
    <div class="card border-0 shadow-sm p-4">
        <form method="POST" action="{{ $category->id ? route('categories.update', $category) : route('categories.store') }}">
            @csrf
            @if($category->id) @method('PUT') @endif
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input name="name" value="{{ old('name', $category->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" rows="3" class="form-control">{{ old('description', $category->description) }}</textarea>
            </div>
            @if($category->id)
            <div class="mb-3 form-check">
                <input type="checkbox" name="active" id="active" class="form-check-input" value="1" {{ $category->active ? 'checked' : '' }}>
                <label for="active" class="form-check-label">Ativa</label>
            </div>
            @endif
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
