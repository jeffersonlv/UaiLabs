@extends('layouts.app')
@section('title', 'Solicitação de Compras')
@section('content')

{{-- Formulário de solicitação --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('purchase-items.store') }}" class="d-flex gap-2 flex-wrap align-items-end">
            @csrf
            <div class="flex-grow-1" style="min-width: 200px">
                <label class="form-label small mb-1 fw-semibold">Produto</label>
                <input type="text" name="name" class="form-control"
                       placeholder="Ex: Café, Detergente, Papel toalha..."
                       required autofocus autocomplete="off">
            </div>
            @if($units->count() > 1)
            <div style="min-width: 160px">
                <label class="form-label small mb-1 fw-semibold">Filial (opcional)</label>
                <select name="unit_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="btn btn-primary px-4">Solicitar</button>
        </form>
    </div>
</div>

{{-- Filtros --}}
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('purchase-items.index', ['filter' => 'all']) }}"
       class="btn btn-sm {{ $filter === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">Todos</a>
    <a href="{{ route('purchase-items.index', ['filter' => 'pending']) }}"
       class="btn btn-sm {{ $filter === 'pending' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
        <i class="bi bi-clock me-1"></i>Falta comprar
    </a>
    <a href="{{ route('purchase-items.index', ['filter' => 'done']) }}"
       class="btn btn-sm {{ $filter === 'done' ? 'btn-success' : 'btn-outline-success' }}">
        <i class="bi bi-check2 me-1"></i>Comprado
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible py-2 mb-3">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Timeline 7 dias --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-bordered mb-0" style="min-width: 700px; table-layout: fixed">
            <thead>
                <tr>
                    @foreach($days as $day)
                    <th class="text-center py-2 {{ $day->isToday() ? 'bg-primary text-white' : 'table-light' }}"
                        style="width: {{ 100 / $days->count() }}%">
                        <div class="small fw-semibold text-capitalize">
                            {{ $day->locale('pt_BR')->isoFormat('ddd') }}
                        </div>
                        <div class="{{ $day->isToday() ? 'text-white-50' : 'text-muted' }}" style="font-size:.75rem">
                            {{ $day->format('d/m') }}
                        </div>
                        @if($day->isToday())
                            <span class="badge bg-white text-primary" style="font-size:.6rem">hoje</span>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr class="align-top">
                    @foreach($days as $day)
                    <td class="{{ $day->isToday() ? 'bg-primary bg-opacity-10' : '' }}" style="padding: 8px; vertical-align: top">
                        @forelse($recent->get($day->toDateString(), collect()) as $item)
                        <div class="d-flex align-items-start gap-1 mb-2">
                            <form method="POST" action="{{ route('purchase-items.toggle', $item) }}" class="flex-shrink-0 mt-1">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn p-0 border-0 bg-transparent lh-1" title="Alternar status">
                                    @if($item->status === 'done')
                                        <i class="bi bi-check-square-fill text-success fs-6"></i>
                                    @else
                                        <i class="bi bi-square text-secondary fs-6"></i>
                                    @endif
                                </button>
                            </form>
                            <div class="overflow-hidden">
                                <div class="small {{ $item->status === 'done' ? 'text-decoration-line-through text-muted' : '' }}"
                                     style="word-break: break-word">{{ $item->name }}</div>
                                @if($item->unit)
                                <div style="font-size:.68rem" class="text-muted">{{ $item->unit->name }}</div>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-muted text-center" style="font-size:.75rem; padding-top: 4px">—</div>
                        @endforelse
                    </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Pendências antigas --}}
@if($oldPending->isNotEmpty())
<div class="card border-0 shadow-sm border-start border-warning border-3">
    <div class="card-header bg-white d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-circle text-warning"></i>
        <span class="fw-semibold">Pendências antigas</span>
        <span class="badge bg-warning text-dark">{{ $oldPending->count() }}</span>
        <span class="text-muted small ms-1">(antes de {{ \Carbon\Carbon::today()->subDays(6)->format('d/m') }})</span>
    </div>
    <ul class="list-group list-group-flush">
        @foreach($oldPending as $item)
        <li class="list-group-item d-flex justify-content-between align-items-center gap-2 py-2">
            <div>
                <span class="small">{{ $item->name }}</span>
                @if($item->unit)
                <span class="text-muted small"> · {{ $item->unit->name }}</span>
                @endif
                <div class="text-muted" style="font-size:.7rem">
                    Solicitado em {{ $item->requested_at->format('d/m/Y') }}
                </div>
            </div>
            <form method="POST" action="{{ route('purchase-items.toggle', $item) }}" class="flex-shrink-0">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-check2 me-1"></i>Comprado
                </button>
            </form>
        </li>
        @endforeach
    </ul>
</div>
@endif

@endsection
