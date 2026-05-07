@extends('layouts.app')
@section('title', 'Tipos de Turno')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Tipos de Turno</h4>
    <a href="{{ route('work-schedules.create') }}" class="btn btn-primary btn-sm">+ Novo tipo</a>
</div>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Nome</th><th>Horas semanais</th><th>Padrão</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($schedules as $s)
            <tr>
                <td class="fw-medium">{{ $s->name }}</td>
                <td>{{ $s->weekly_hours }}h</td>
                <td>{{ $s->is_default ? '<span class="badge bg-success">Sim</span>' : '—' }}</td>
                <td><span class="badge bg-{{ $s->active ? 'success' : 'secondary' }}">{{ $s->active ? 'Ativo' : 'Inativo' }}</span></td>
                <td class="text-end">
                    <a href="{{ route('work-schedules.edit', $s) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted text-center py-3">Nenhum tipo cadastrado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection