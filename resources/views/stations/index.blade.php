@extends('layouts.app')
@section('title', 'Estações')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Estações de Trabalho</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newStationModal">
            <i class="bi bi-plus-lg me-1"></i>Nova estação
        </button>
        <a href="{{ route('shifts.timesheet') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @forelse($stations as $station)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom station-row" data-id="{{ $station->id }}">
            {{-- Color swatch --}}
            <span class="rounded-circle d-inline-block flex-shrink-0"
                  style="width:20px;height:20px;background:{{ $station->color }}"></span>

            {{-- Name --}}
            <span class="fw-semibold flex-grow-1">{{ $station->name }}</span>

            {{-- Status badge --}}
            <span class="badge {{ $station->active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                {{ $station->active ? 'Ativa' : 'Inativa' }}
            </span>

            {{-- Actions --}}
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary py-0"
                        onclick="openEdit({{ $station->id }}, '{{ addslashes($station->name) }}', '{{ $station->color }}', {{ $station->active ? 'true' : 'false' }})">
                    <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="{{ route('stations.destroy', $station) }}"
                      onsubmit="return confirm('Remover estação {{ addslashes($station->name) }}?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger py-0">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">Nenhuma estação cadastrada.</div>
        @endforelse
    </div>
</div>

{{-- Modal: Nova estação --}}
<div class="modal fade" id="newStationModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="{{ route('stations.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Nova Estação</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nome</label>
                        <input type="text" name="name" class="form-control form-control-sm" required maxlength="60" placeholder="ex: Cozinha">
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Cor</label>
                        <input type="color" name="color" class="form-control form-control-color" value="#0d6efd">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Editar estação --}}
<div class="modal fade" id="editStationModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" id="editForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Editar Estação</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nome</label>
                        <input type="text" name="name" id="editName" class="form-control form-control-sm" required maxlength="60">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cor</label>
                        <input type="color" name="color" id="editColor" class="form-control form-control-color">
                    </div>
                    <div class="form-check">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" name="active" id="editActive" class="form-check-input" value="1">
                        <label class="form-check-label small" for="editActive">Ativa</label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, name, color, active) {
    document.getElementById('editForm').action = '/stations/' + id;
    document.getElementById('editName').value  = name;
    document.getElementById('editColor').value = color;
    document.getElementById('editActive').checked = active;
    new bootstrap.Modal(document.getElementById('editStationModal')).show();
}
</script>
@endsection
