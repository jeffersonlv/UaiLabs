@extends('layouts.app')
@section('title', 'Correções de Ponto')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Correções de Ponto</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#correctionModal">+ Nova correção</button>
</div>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Funcionário</th><th>Unidade</th><th>Tipo</th><th>Registrado em</th><th>Justificativa</th></tr>
        </thead>
        <tbody>
            @forelse($corrections as $e)
            <tr>
                <td>{{ $e->user?->name }}</td>
                <td>{{ $e->unit?->name ?? '—' }}</td>
                <td>
                    @if($e->type === 'clock_in')
                        <span class="badge bg-success">Entrada</span>
                    @elseif($e->type === 'clock_out')
                        <span class="badge bg-primary">Saída</span>
                    @else
                        <span class="badge bg-secondary">{{ $e->type }}</span>
                    @endif
                </td>
                <td>{{ $e->recorded_at->format('d/m/Y H:i') }}</td>
                <td class="text-muted small">{{ $e->justification }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-muted text-center py-3">Nenhuma correção registrada.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($corrections->hasPages())
    <div class="card-footer bg-white">{{ $corrections->links() }}</div>
    @endif
</div>

{{-- Modal --}}
<div class="modal fade" id="correctionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Nova Correção</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('time-entries.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Funcionário</label>
                        <select name="user_id" class="form-select" required>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de registro</label>
                        <select name="type" class="form-select" required>
                            <option value="clock_in">Entrada</option>
                            <option value="clock_out">Saída</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unidade</label>
                        <select name="unit_id" class="form-select" required>
                            @foreach($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data e hora correta</label>
                        <input type="datetime-local" name="recorded_at" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Justificativa <span class="text-danger">*</span></label>
                        <textarea name="justification" rows="2" class="form-control" required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection