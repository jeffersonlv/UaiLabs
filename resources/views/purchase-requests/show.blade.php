@extends('layouts.app')
@section('title', 'Solicitação de Compra')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Solicitação #{{ $purchaseRequest->id }}</h4>
    <a href="{{ route('purchase-requests.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>
<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-4">
            <dl class="row mb-0">
                <dt class="col-5">Produto</dt>
                <dd class="col-7">{{ $purchaseRequest->product_name }}</dd>
                <dt class="col-5">Quantidade</dt>
                <dd class="col-7">{{ $purchaseRequest->quantity_text ?: '—' }}</dd>
                <dt class="col-5">Unidade</dt>
                <dd class="col-7">{{ $purchaseRequest->unit?->name ?? '—' }}</dd>
                <dt class="col-5">Observações</dt>
                <dd class="col-7 text-muted">{{ $purchaseRequest->notes ?: '—' }}</dd>
                <dt class="col-5">Status</dt>
                <dd class="col-7"><span class="badge bg-{{ $purchaseRequest->statusColor() }}">{{ $purchaseRequest->statusLabel() }}</span></dd>
                <dt class="col-5">Criado em</dt>
                <dd class="col-7 text-muted">{{ $purchaseRequest->created_at->format('d/m/Y H:i') }}</dd>
                @if($purchaseRequest->status_changed_at)
                <dt class="col-5">Atualizado em</dt>
                <dd class="col-7 text-muted">{{ $purchaseRequest->status_changed_at->format('d/m/Y H:i') }} por {{ $purchaseRequest->statusChangedBy?->name }}</dd>
                @endif
                @if($purchaseRequest->cancellation_reason)
                <dt class="col-5">Motivo</dt>
                <dd class="col-7 text-muted">{{ $purchaseRequest->cancellation_reason }}</dd>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection