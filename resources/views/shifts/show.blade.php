@extends('layouts.app')
@section('title', 'Turno')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Detalhes do Turno</h4>
    <div class="d-flex gap-2">
        @if(auth()->user()->isManagerOrAbove())
            <form method="POST" action="{{ route('shifts.destroy', $shift) }}">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Excluir turno?')">Excluir</button>
            </form>
        @endif
        <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>
</div>
<div class="card border-0 shadow-sm p-4" style="max-width:480px">
    <dl class="row mb-0">
        <dt class="col-5">Funcionário</dt>
        <dd class="col-7">{{ $shift->user->name }}</dd>
        <dt class="col-5">Unidade</dt>
        <dd class="col-7">{{ $shift->unit->name }}</dd>
        <dt class="col-5">Tipo</dt>
        <dd class="col-7"><span class="badge bg-{{ $shift->typeColor() }}">{{ $shift->typeLabel() }}</span></dd>
        <dt class="col-5">Início</dt>
        <dd class="col-7">{{ $shift->start_at->format('d/m/Y H:i') }}</dd>
        <dt class="col-5">Fim</dt>
        <dd class="col-7">{{ $shift->end_at->format('d/m/Y H:i') }}</dd>
        <dt class="col-5">Duração</dt>
        <dd class="col-7">{{ \App\Services\TimeCalculationService::formatMinutes($shift->durationMinutes()) }}</dd>
        @if($shift->notes)
        <dt class="col-5">Notas</dt>
        <dd class="col-7 text-muted">{{ $shift->notes }}</dd>
        @endif
        <dt class="col-5">Criado por</dt>
        <dd class="col-7 text-muted">{{ $shift->creator->name }} em {{ $shift->created_at->format('d/m/Y') }}</dd>
    </dl>
</div>
@endsection