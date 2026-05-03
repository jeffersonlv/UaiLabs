@extends('layouts.app')
@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Solicitações</h4>
    <span class="text-muted small">
        {{ $totalAll }} total
        @if($totalImportant > 0)
            &nbsp;·&nbsp;<span class="text-warning">&#9733; {{ $totalImportant }} importante{{ $totalImportant > 1 ? 's' : '' }}</span>
        @endif
    </span>
</div>

{{-- ── Abas de status ──────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-3">
    @php
        $tabs = [
            'all'       => ['Todas',    $totalAll],
            'avaliar'   => ['Avaliar',  $counts->get('avaliar',  0)],
            'fazer'     => ['Fazer',    $counts->get('fazer',    0)],
            'perguntar' => ['Perguntar',$counts->get('perguntar',0)],
            'feito'     => ['Feito',    $counts->get('feito',    0)],
        ];
    @endphp
    @foreach($tabs as $key => [$label, $count])
        <li class="nav-item">
            <a class="nav-link {{ $status === $key ? 'active' : '' }}"
               href="{{ route('admin.support-requests.index', ['status' => $key]) }}">
                {{ $label }}
                <span class="badge {{ $status === $key ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ $count }}</span>
            </a>
        </li>
    @endforeach
</ul>

{{-- ── Tabela ──────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:42px" class="text-center">&#9733;</th>
                    <th>Solicitação</th>
                    <th style="width:145px">Empresa</th>
                    <th style="width:145px">Prioridade</th>
                    <th style="width:145px">Status</th>
                    <th style="width:120px">Data</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr class="{{ $req->important ? 'table-warning' : '' }}">

                    {{-- Estrela de importante --}}
                    <td class="text-center">
                        <form method="POST" action="{{ route('admin.support-requests.important', $req) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-link p-0 fs-5 lh-1
                                {{ $req->important ? 'text-warning' : 'text-muted' }}"
                                title="{{ $req->important ? 'Remover importância' : 'Marcar como importante' }}">
                                {{ $req->important ? '★' : '☆' }}
                            </button>
                        </form>
                    </td>

                    {{-- Título + solicitante --}}
                    <td>
                        <a href="{{ route('admin.support-requests.show', $req) }}"
                           class="fw-semibold text-dark text-decoration-none">
                            {{ $req->title }}
                        </a>
                        <div class="text-muted small">{{ $req->user->name ?? '—' }}</div>
                    </td>

                    {{-- Empresa --}}
                    <td class="small text-muted">{{ $req->company->name ?? '—' }}</td>

                    {{-- Prioridade (inline update) --}}
                    <td>
                        <form method="POST" action="{{ route('admin.support-requests.update', $req) }}">
                            @csrf @method('PATCH')
                            <select name="priority" class="form-select form-select-sm"
                                    onchange="this.form.submit()">
                                <option value="">— sem prioridade</option>
                                <option value="1" {{ $req->priority == 1 ? 'selected' : '' }}>&#9650; Alta</option>
                                <option value="2" {{ $req->priority == 2 ? 'selected' : '' }}>&#9654; Média</option>
                                <option value="3" {{ $req->priority == 3 ? 'selected' : '' }}>&#9660; Baixa</option>
                            </select>
                        </form>
                    </td>

                    {{-- Status (inline update) --}}
                    <td>
                        <form method="POST" action="{{ route('admin.support-requests.update', $req) }}">
                            @csrf @method('PATCH')
                            <select name="status" class="form-select form-select-sm"
                                    onchange="this.form.submit()">
                                <option value="avaliar"   {{ $req->status === 'avaliar'   ? 'selected' : '' }}>Avaliar</option>
                                <option value="fazer"     {{ $req->status === 'fazer'     ? 'selected' : '' }}>Fazer</option>
                                <option value="perguntar" {{ $req->status === 'perguntar' ? 'selected' : '' }}>Perguntar</option>
                                <option value="feito"     {{ $req->status === 'feito'     ? 'selected' : '' }}>Feito</option>
                            </select>
                        </form>
                    </td>

                    <td class="text-muted small">{{ $req->created_at->format('d/m/Y') }}</td>

                    <td>
                        <a href="{{ route('admin.support-requests.show', $req) }}"
                           class="btn btn-sm btn-outline-secondary">Ver</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">Nenhuma solicitação encontrada.</td>
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
