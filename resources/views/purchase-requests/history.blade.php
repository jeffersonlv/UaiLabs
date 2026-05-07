@extends('layouts.app')
@section('title', 'Histórico de Compras')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Histórico de Compras</h4>
    <a href="{{ route('purchase-requests.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Produto</th>
                <th>Qtd</th>
                <th>Unidade</th>
                @if($user->isManagerOrAbove()) <th>Solicitante</th> @endif
                <th>Status</th>
                <th>Atualizado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($history as $pr)
            @php
                $cancelled  = $pr->status === 'cancelled';
                $purchased  = $pr->status === 'purchased';
            @endphp
            <tr class="{{ $cancelled ? 'table-light text-muted' : '' }}">
                <td class="{{ $purchased ? 'text-decoration-line-through' : '' }}">{{ $pr->product_name }}</td>
                <td>{{ $pr->quantity }} {{ $pr->uomLabel() }}</td>
                <td>{{ $pr->unit?->name ?? '—' }}</td>
                @if($user->isManagerOrAbove()) <td>{{ $pr->user?->name ?? '—' }}</td> @endif
                <td><span class="badge bg-{{ $pr->statusColor() }}">{{ $pr->statusLabel() }}</span></td>
                <td class="text-muted small">{{ $pr->status_changed_at?->format('d/m/Y H:i') ?? $pr->updated_at->format('d/m/Y H:i') }}</td>
                <td><a href="{{ route('purchase-requests.show', $pr) }}" class="btn btn-sm btn-outline-secondary py-0">Ver</a></td>
            </tr>
            @empty
            <tr><td colspan="{{ $user->isManagerOrAbove() ? 7 : 6 }}" class="text-muted text-center py-3">Nenhum registro.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($history->hasPages())
    <div class="card-footer bg-white">{{ $history->links() }}</div>
    @endif
</div>
@endsection