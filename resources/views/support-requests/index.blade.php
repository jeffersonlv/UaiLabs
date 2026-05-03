@extends('layouts.app')
@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0">Minhas Solicitações</h4>
    <a href="{{ route('support-requests.create') }}" class="btn btn-primary btn-sm">+ Nova Solicitação</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Título</th>
                    <th style="width:130px">Status</th>
                    <th style="width:120px">Prioridade</th>
                    <th style="width:140px">Enviada em</th>
                    <th style="width:36px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td>
                        <a href="{{ route('support-requests.show', $req) }}" class="fw-semibold text-dark text-decoration-none">{{ $req->title }}</a>
                        @if($req->notes_count ?? $req->notes->count() ?? 0)
                            <span class="badge bg-secondary ms-1" style="font-size:.65rem">{{ $req->notes->count() }} nota(s)</span>
                        @endif
                        @if($req->superadmin_note)
                            <div class="text-muted small mt-1">
                                <span class="text-primary fw-bold">&#9656;</span>
                                <em>{{ Str::limit($req->superadmin_note, 100) }}</em>
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ \App\Models\SupportRequest::statusBadge($req->status) }}">
                            {{ \App\Models\SupportRequest::statusLabel($req->status) }}
                        </span>
                    </td>
                    <td>
                        @if($req->priority)
                            <span class="badge {{ \App\Models\SupportRequest::priorityBadge($req->priority) }}">
                                {{ \App\Models\SupportRequest::priorityLabel($req->priority) }}
                            </span>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center">
                        @if($req->important)
                            <span class="text-warning fs-5" title="Marcada como importante">&#9733;</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        Nenhuma solicitação enviada ainda.<br>
                        <a href="{{ route('support-requests.create') }}">Criar a primeira agora.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($requests->hasPages())
    <div class="mt-3 d-flex justify-content-center">{{ $requests->links() }}</div>
@endif

@endsection