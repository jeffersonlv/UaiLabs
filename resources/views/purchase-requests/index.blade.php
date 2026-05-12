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
                @if($units->isNotEmpty())
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Filial / Unidade</label>
                    <select name="unit_id" class="form-select form-select-sm">
                        <option value="">— Geral —</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-{{ $units->isNotEmpty() ? '5' : '8' }}">
                    <label class="form-label form-label-sm">Produto <span class="text-danger">*</span></label>
                    <input name="product_name" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Quantidade</label>
                    <input name="quantity_text" class="form-control form-control-sm" placeholder="ex: 2 cx, 500g">
                </div>
                <div class="col-md-2 col-12">
                    <label class="form-label form-label-sm">Observações</label>
                    <input name="notes" class="form-control form-control-sm" placeholder="Opcional">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm">Solicitar</button>
            </div>
        </form>
    </div>
</div>

{{-- Active requests --}}
@php $statusOrder = ['requested' => 0, 'ordered' => 1, 'purchased' => 2, 'received' => 3]; @endphp
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Solicitações ativas</div>
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Produto</th>
                <th>Qtd</th>
                @if($units->isNotEmpty())<th>Unidade</th>@endif
                @if($user->isManagerOrAbove())<th>Solicitado por</th>@endif
                <th>Status</th>
                <th>Data</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($active as $pr)
            @php $currentOrder = $statusOrder[$pr->status] ?? 0; @endphp
            <tr>
                <td class="fw-medium">{{ $pr->product_name }}</td>
                <td class="text-muted small">{{ $pr->quantity_text ?: '—' }}</td>
                @if($units->isNotEmpty())<td class="text-muted small">{{ $pr->unit?->name ?? '—' }}</td>@endif
                @if($user->isManagerOrAbove())<td class="text-muted small">{{ $pr->user?->name ?? '—' }}</td>@endif
                <td><span class="badge bg-{{ $pr->statusColor() }}">{{ $pr->statusLabel() }}</span></td>
                <td class="text-muted small">{{ $pr->created_at->format('d/m H:i') }}</td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                        @foreach(['ordered' => 'Pedido', 'purchased' => 'Comprado', 'received' => 'Recebido'] as $s => $label)
                            @if(($statusOrder[$s] ?? 0) > $currentOrder)
                            <form method="POST" action="{{ route('purchase-requests.status', $pr) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $s }}">
                                <button class="btn btn-sm btn-outline-secondary py-0" style="font-size:.7rem">{{ $label }}</button>
                            </form>
                            @endif
                        @endforeach
                        <button type="button"
                                class="btn btn-sm btn-outline-danger py-0"
                                style="font-size:.7rem"
                                data-bs-toggle="modal" data-bs-target="#cancelModal"
                                data-url="{{ route('purchase-requests.status', $pr) }}"
                                data-product="{{ $pr->product_name }}">
                            Cancelar
                        </button>
                        <a href="{{ route('purchase-requests.show', $pr) }}" class="btn btn-sm btn-outline-info py-0" style="font-size:.7rem">Ver</a>
                    </div>
                </td>
            </tr>
            @empty
            @php $cols = 5 + ($units->isNotEmpty() ? 1 : 0) + ($user->isManagerOrAbove() ? 1 : 0); @endphp
            <tr><td colspan="{{ $cols }}" class="text-muted text-center py-3">Nenhuma solicitação ativa.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal: Cancelamento --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" id="cancelForm">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="cancelled">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Cancelar solicitação</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">Produto: <strong id="cancelProduct"></strong></p>
                    <label class="form-label form-label-sm">Motivo</label>
                    @foreach(\App\Models\PurchaseRequest::CANCEL_REASONS as $key => $label)
                    <div class="form-check">
                        <input class="form-check-input cancel-reason-radio" type="radio"
                               name="cancel_reason" id="cr_{{ $key }}" value="{{ $key }}"
                               {{ $loop->first ? 'checked' : '' }}>
                        <label class="form-check-label" for="cr_{{ $key }}" style="font-size:.85rem">{{ $label }}</label>
                    </div>
                    @endforeach
                    <div id="cancelCustomWrap" class="mt-2" style="display:none">
                        <input name="cancel_reason_custom" class="form-control form-control-sm"
                               placeholder="Descreva o motivo..." maxlength="200">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirmar cancelamento</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('cancelModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('cancelForm').action = btn.dataset.url;
    document.getElementById('cancelProduct').textContent = btn.dataset.product;
});

document.querySelectorAll('.cancel-reason-radio').forEach(function (r) {
    r.addEventListener('change', function () {
        document.getElementById('cancelCustomWrap').style.display =
            this.value === 'personalizado' ? 'block' : 'none';
    });
});
</script>
@endpush
@endsection
