@extends('layouts.app')
@section('title', 'Solicitação de Compras')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-cart-plus me-2"></i>Solicitação de Compras</h4>
    <a href="{{ route('purchase-requests.history') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-clock-history me-1"></i>Histórico
    </a>
</div>

{{-- Form: Nova solicitação --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Nova solicitação</div>
    <div class="card-body">
        <form method="POST" action="{{ route('purchase-requests.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Unidade</label>
                    <select name="unit_id" class="form-select form-select-sm" required>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Produto <span class="text-danger">*</span></label>
                    <input name="product_name" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Qtd <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control form-control-sm" step="0.001" min="0.001" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Unidade</label>
                    <select name="unit_of_measure" class="form-select form-select-sm">
                        @foreach(\App\Models\PurchaseRequest::UNITS_OF_MEASURE as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label form-label-sm">Observações</label>
                    <input name="notes" class="form-control form-control-sm">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm">Solicitar</button>
            </div>
        </form>
    </div>
</div>

{{-- Active requests --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Solicitações ativas</div>
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Produto</th>
                <th>Qtd</th>
                <th>Unidade</th>
                @if($user->isManagerOrAbove()) <th>Solicitado por</th> @endif
                <th>Status</th>
                <th>Data</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($active as $pr)
            @php
                $isPurchased = $pr->status === 'purchased';
                $rowClass    = $isPurchased ? 'text-decoration-line-through text-muted' : '';
            @endphp
            <tr class="{{ $rowClass }}">
                <td class="fw-medium">{{ $pr->product_name }}</td>
                <td>{{ rtrim(rtrim(number_format($pr->quantity, 3, ',', '.'), '0'), ',') }} {{ $pr->uomLabel() }}</td>
                <td>{{ $pr->unit?->name ?? '—' }}</td>
                @if($user->isManagerOrAbove()) <td>{{ $pr->user?->name ?? '—' }}</td> @endif
                <td>
                    <span class="badge bg-{{ $pr->statusColor() }}">{{ $pr->statusLabel() }}</span>
                </td>
                <td class="text-muted small">{{ $pr->created_at->format('d/m H:i') }}</td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end">
                        @if($user->isManagerOrAbove())
                            @foreach(['ordered'=>'Pedido','purchased'=>'Comprado'] as $s => $label)
                                @if($pr->status !== $s)
                                <form method="POST" action="{{ route('purchase-requests.status', $pr) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $s }}">
                                    <button class="btn btn-sm btn-outline-secondary py-0" style="font-size:.7rem">{{ $label }}</button>
                                </form>
                                @endif
                            @endforeach
                        @endif
                        @if($pr->canBeCancelledBy($user))
                        <form method="POST" action="{{ route('purchase-requests.status', $pr) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button class="btn btn-sm btn-outline-danger py-0" style="font-size:.7rem" onclick="return confirm('Cancelar?')">Cancelar</button>
                        </form>
                        @endif
                        <a href="{{ route('purchase-requests.show', $pr) }}" class="btn btn-sm btn-outline-info py-0" style="font-size:.7rem">Ver</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ $user->isManagerOrAbove() ? 7 : 6 }}" class="text-muted text-center py-3">Nenhuma solicitação ativa.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection