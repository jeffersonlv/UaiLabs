@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Subcategorias</h4>
    <a href="{{ route('subcategories.create') }}" class="btn btn-primary btn-sm">+ Nova subcategoria</a>
</div>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Nome</th><th>Categoria</th><th class="text-center">Ordem</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($subcategories as $sub)
            <tr>
                <td class="fw-medium">{{ $sub->name }}</td>
                <td>{{ $sub->category->name ?? '—' }}</td>
                <td class="text-center">{{ $sub->order }}</td>
                <td class="text-end">
                    <a href="{{ route('subcategories.edit', $sub) }}" class="btn btn-sm btn-outline-secondary me-1">Editar</a>
                    <form method="POST" action="{{ route('subcategories.destroy', $sub) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover?')">Remover</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-muted text-center py-3">Nenhuma subcategoria cadastrada.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($subcategories->hasPages())
    <div class="card-footer bg-white">{{ $subcategories->links() }}</div>
    @endif
</div>
@endsection