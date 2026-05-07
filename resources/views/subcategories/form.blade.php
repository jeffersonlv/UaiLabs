@extends('layouts.app')
@section('content')
<div style="max-width:480px">
    <h4 class="mb-4">{{ $subcategory->id ? 'Editar' : 'Nova' }} subcategoria</h4>
    <div class="card border-0 shadow-sm p-4">
        <form method="POST" action="{{ $subcategory->id ? route('subcategories.update', $subcategory) : route('subcategories.store') }}">
            @csrf
            @if($subcategory->id) @method('PUT') @endif
            <div class="mb-3">
                <label class="form-label">Categoria <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $subcategory->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Nome <span class="text-danger">*</span></label>
                <input name="name" value="{{ old('name', $subcategory->name) }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ordem</label>
                <input type="number" name="order" value="{{ old('order', $subcategory->order ?? 0) }}" min="0" class="form-control" style="width:100px">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('subcategories.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection