@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Filiais</h4>
        <small class="text-muted">{{ $units->count() }} unidade(s) cadastrada(s)</small>
    </div>
    <a href="{{ route('units.create') }}" class="btn btn-primary btn-sm">+ Nova Unidade</a>
</div>

<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Endereço</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($units as $unit)
            <tr>
                <td class="fw-semibold">{{ $unit->name }}</td>
                <td>
                    <span class="badge bg-{{ $unit->type === 'matriz' ? 'primary' : 'secondary' }}">
                        {{ $unit->typeLabel() }}
                    </span>
                </td>
                <td class="text-muted small">{{ $unit->address ?? '—' }}</td>
                <td>
                    <span class="badge bg-{{ $unit->active ? 'success' : 'secondary' }}">
                        {{ $unit->active ? 'Ativa' : 'Inativa' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                    <form method="POST" action="{{ route('units.destroy', $unit) }}"
                          class="d-inline" onsubmit="return confirm('Excluir unidade {{ $unit->name }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Excluir</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma unidade cadastrada.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
