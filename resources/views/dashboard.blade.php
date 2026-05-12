@extends('layouts.app')
@section('content')

{{-- ── Cabeçalho ─────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Dashboard</h4>
        @if(auth()->user()->isSuperAdmin())
            <span class="text-muted small">
                @if($filterCompany)
                    {{ $filterCompany->name }}
                    @if($visibleUnits->count() === 1)
                        — <span class="fw-semibold">{{ $visibleUnits->first()->name }}</span>
                        <span class="badge bg-secondary ms-1" style="font-size:.65rem">{{ $visibleUnits->first()->typeLabel() }}</span>
                    @elseif($selectedUnitId === null)
                        — todas as unidades
                    @endif
                @else
                    Todas as empresas
                @endif
            </span>
        @elseif($visibleUnits->isEmpty())
            <span class="text-muted small">{{ auth()->user()->company?->name }}</span>
        @elseif($visibleUnits->count() === 1)
            <span class="text-muted small">
                {{ auth()->user()->company?->name }} —
                <span class="fw-semibold">{{ $visibleUnits->first()->name }}</span>
                <span class="badge bg-secondary ms-1" style="font-size:.65rem">{{ $visibleUnits->first()->typeLabel() }}</span>
            </span>
        @else
            <span class="text-muted small">
                {{ auth()->user()->company?->name }} —
                {{ $visibleUnits->pluck('name')->join(', ') }}
            </span>
        @endif
    </div>
    <span class="text-muted small">
        @if($isRange)
            {{ $dateFrom->format('d/m/Y') }} — {{ $dateTo->format('d/m/Y') }}
            <span class="badge bg-secondary ms-1">{{ $days }} {{ $days === 1 ? 'dia' : 'dias' }}</span>
        @else
            {{ $date->format('d/m/Y') }}
            @if($date->isToday()) <span class="badge bg-primary ms-1">hoje</span> @endif
        @endif
    </span>
</div>

{{-- ── Filtros de data ────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-end">
            {{-- Hoje --}}
            <div class="col-auto">
                @php
                    $todayParams = array_filter([
                        'company_id' => $selectedCompanyId ?? null,
                        'unit_id'    => $selectedUnitId ?? null,
                    ]);
                @endphp
                <a href="{{ route('dashboard', $todayParams) }}"
                   class="btn btn-sm {{ !$isRange && $date->isToday() ? 'btn-primary' : 'btn-outline-primary' }}">
                    Hoje
                </a>
            </div>

            {{-- Data única --}}
            <div class="col-auto d-flex gap-2 align-items-end">
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Data única</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                        value="{{ !$isRange && !$date->isToday() ? $date->toDateString() : '' }}"
                        max="{{ now()->toDateString() }}">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Ver</button>
            </div>

            <div class="col-auto text-muted small px-1">ou</div>

            {{-- Range --}}
            <div class="col-auto d-flex gap-2 align-items-end">
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">De</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ $isRange ? $dateFrom->toDateString() : '' }}"
                        max="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Até</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="{{ $isRange ? $dateTo->toDateString() : '' }}"
                        max="{{ now()->toDateString() }}">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Ver período</button>
            </div>

            @if($allCompanies->isNotEmpty())
            <div class="col-auto d-flex gap-2 align-items-end ms-auto">
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Empresa</label>
                    <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:180px">
                        <option value="">Todas as empresas</option>
                        @foreach($allCompanies as $c)
                            <option value="{{ $c->id }}" {{ $selectedCompanyId == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($companyUnits->isNotEmpty())
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Unidade</label>
                    <select name="unit_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:160px">
                        <option value="">Todas</option>
                        @foreach($companyUnits as $unit)
                            <option value="{{ $unit->id }}" {{ $selectedUnitId == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }} — {{ $unit->typeLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            @elseif($companyUnits->isNotEmpty())
            <div class="col-auto d-flex gap-2 align-items-end ms-auto">
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Unidade</label>
                    <select name="unit_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:180px">
                        <option value="">Todas as unidades</option>
                        @foreach($companyUnits as $unit)
                            <option value="{{ $unit->id }}" {{ $selectedUnitId == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }} — {{ $unit->typeLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- ── Cards de totais ────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="card border-0 bg-secondary bg-opacity-10">
            <div class="card-body py-3">
                <div class="text-muted small">Total</div>
                <div class="fs-3 fw-semibold">{{ $total }}</div>
                @if($isRange)<div class="text-muted" style="font-size:.75rem">{{ $days }} {{ $days === 1 ? 'dia' : 'dias' }}</div>@endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 bg-success bg-opacity-10">
            <div class="card-body py-3">
                <div class="text-muted small">Concluídas</div>
                <div class="fs-3 fw-semibold text-success">{{ $done }}</div>
                @if($isRange)<div class="text-muted" style="font-size:.75rem">média {{ $avgDone }}/dia</div>@endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 bg-warning bg-opacity-10">
            <div class="card-body py-3">
                <div class="text-muted small">Pendentes</div>
                <div class="fs-3 fw-semibold text-warning">{{ $pending }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 bg-danger bg-opacity-10">
            <div class="card-body py-3">
                <div class="text-muted small">Atrasadas</div>
                <div class="fs-3 fw-semibold text-danger">{{ $overdue }}</div>
                @if($isRange)<div class="text-muted" style="font-size:.75rem">média {{ $avgNot }}/dia (c/ pend.)</div>@endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 bg-primary bg-opacity-10">
            <div class="card-body py-3">
                <div class="text-muted small">Taxa</div>
                <div class="fs-3 fw-semibold text-primary">{{ $rate }}%</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Gráfico de conclusão (Chart.js) ─────────────────────── --}}
@if($isRange)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small">Taxa de conclusão por dia</span>
        <div class="d-flex gap-1">
            @if(auth()->user()->isSuperAdmin())
                @foreach(['geral'=>'Geral','empresa'=>'Por empresa','filial'=>'Por filial'] as $v => $l)
                    <a href="#" class="btn btn-sm {{ request('chart_group','geral') === $v ? 'btn-secondary' : 'btn-outline-secondary' }} py-0 chart-group-btn" data-group="{{ $v }}" style="font-size:.7rem">{{ $l }}</a>
                @endforeach
            @else
                @foreach(['geral'=>'Geral','filial'=>'Por filial'] as $v => $l)
                    <a href="#" class="btn btn-sm {{ request('chart_group','geral') === $v ? 'btn-secondary' : 'btn-outline-secondary' }} py-0 chart-group-btn" data-group="{{ $v }}" style="font-size:.7rem">{{ $l }}</a>
                @endforeach
            @endif
        </div>
    </div>
    <div class="card-body p-3">
        <canvas id="completionChart" height="80"></canvas>
    </div>
</div>
@endif

{{-- ── Detalhamento diário (só no range) ─────────────────── --}}
@if($isRange && $daily->isNotEmpty())
<h6 class="mb-3">Detalhamento diário</h6>
<div class="card border-0 shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th class="text-end">Total</th>
                    <th class="text-end text-success">Concluídas</th>
                    <th class="text-end text-danger">Atrasadas</th>
                    <th class="text-end text-warning">Pendentes</th>
                    <th class="text-end text-primary">Taxa</th>
                </tr>
            </thead>
            <tbody>
                @foreach($daily as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td class="text-end">{{ $row['total'] }}</td>
                    <td class="text-end fw-semibold text-success">{{ $row['done'] }}</td>
                    <td class="text-end text-danger">{{ $row['overdue'] }}</td>
                    <td class="text-end text-warning">{{ $row['pending'] }}</td>
                    <td class="text-end">
                        <span class="badge {{ $row['rate'] >= 80 ? 'bg-success' : ($row['rate'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ $row['rate'] }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── Solicitações (só superadmin) ───────────────────────── --}}
@if($supportStats)
<h6 class="mb-3">
    Solicitações
    <a href="{{ route('admin.support-requests.index') }}" class="text-muted fw-normal small ms-1">ver todas &rarr;</a>
</h6>
<div class="row g-3 mb-4">
    {{-- Contadores por status --}}
    @foreach([
        ['avaliar',   'Avaliar',   'bg-primary',         $supportStats['avaliar']],
        ['fazer',     'Fazer',     'bg-warning',         $supportStats['fazer']],
        ['perguntar', 'Perguntar', 'bg-info',            $supportStats['perguntar']],
        ['feito',     'Feito',     'bg-success',         $supportStats['feito']],
    ] as [$status, $label, $color, $count])
    <div class="col-6 col-sm-3">
        <a href="{{ route('admin.support-requests.index', ['status' => $status]) }}"
           class="card border-0 {{ $color }} bg-opacity-10 text-decoration-none">
            <div class="card-body py-3">
                <div class="text-muted small">{{ $label }}</div>
                <div class="fs-3 fw-semibold">{{ $count }}</div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Fila de pendentes --}}
@if($supportStats['recent']->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small">Pendentes em aberto</span>
        <span class="badge bg-secondary">{{ $supportStats['open'] }} no total</span>
        @if($supportStats['important'] > 0)
            <span class="badge bg-warning text-dark ms-1">&#9733; {{ $supportStats['important'] }} importante{{ $supportStats['important'] > 1 ? 's' : '' }}</span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:32px"></th>
                    <th>Solicitação</th>
                    <th>Empresa</th>
                    <th style="width:110px">Status</th>
                    <th style="width:100px">Prioridade</th>
                    <th style="width:110px">Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($supportStats['recent'] as $req)
                <tr>
                    <td class="text-center">
                        @if($req->important)
                            <span class="text-warning">&#9733;</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.support-requests.show', $req) }}"
                           class="fw-semibold text-dark text-decoration-none">
                            {{ Str::limit($req->title, 55) }}
                        </a>
                        <div class="text-muted" style="font-size:.75rem">{{ $req->user->name ?? '—' }}</div>
                    </td>
                    <td class="text-muted small">{{ $req->company->name ?? '—' }}</td>
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
                    <td class="text-muted small">{{ $req->created_at->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($supportStats['open'] > 5)
    <div class="card-footer bg-transparent text-center py-2">
        <a href="{{ route('admin.support-requests.index') }}" class="small text-muted">
            + {{ $supportStats['open'] - 5 }} outras em aberto
        </a>
    </div>
    @endif
</div>
@endif
@endif

{{-- ── Compras ativas (manager+) ───────────────────────────── --}}
@if($purchaseStats !== null)
<h6 class="mb-3">
    Compras pendentes
    <a href="{{ route('purchase-requests.index') }}" class="text-muted fw-normal small ms-1">ver todas &rarr;</a>
</h6>
<div class="row g-3 mb-4">
    @foreach(\App\Models\PurchaseRequest::STATUSES as $status => $meta)
        @if(in_array($status, \App\Models\PurchaseRequest::ACTIVE_STATUSES))
        <div class="col-6 col-sm-4">
            <a href="{{ route('purchase-requests.index') }}"
               class="card border-0 bg-{{ $meta['color'] }} bg-opacity-10 text-decoration-none">
                <div class="card-body py-3">
                    <div class="text-muted small">{{ $meta['label'] }}</div>
                    <div class="fs-3 fw-semibold">{{ $purchaseStats->get($status, 0) }}</div>
                </div>
            </a>
        </div>
        @endif
    @endforeach
</div>
@endif

{{-- ── Performance por colaborador ───────────────────────── --}}
<h6 class="mb-3">
    Performance por colaborador
    <span class="text-muted fw-normal small">
        — {{ $isRange ? $dateFrom->format('d/m') . ' a ' . $dateTo->format('d/m/Y') : $date->format('d/m/Y') }}
    </span>
</h6>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Colaborador</th>
                <th>Perfil</th>
                <th class="text-end">Tarefas concluídas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byUser as $u)
            <tr>
                <td>{{ $u->name }}</td>
                <td>
                    <span class="badge
                        {{ $u->role === 'admin'   ? 'bg-danger'  :
                          ($u->role === 'manager' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                        {{ ucfirst($u->role) }}
                    </span>
                </td>
                <td class="text-end fw-semibold">{{ $u->done_count }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-muted text-center py-3">Nenhum dado ainda.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@push('scripts')
@if($isRange)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    var chartInstance = null;
    var currentGroup  = '{{ request("chart_group", "geral") }}';

    function loadChart(group) {
        currentGroup = group;
        var params = new URLSearchParams({
            date_from:   '{{ $dateFrom?->toDateString() }}',
            date_to:     '{{ $dateTo?->toDateString() }}',
            group:       group,
            @if($selectedCompanyId) company_id: '{{ $selectedCompanyId }}', @endif
            @if($selectedUnitId)    unit_id:    '{{ $selectedUnitId }}',    @endif
        });

        fetch('/dashboard/completion-chart?' + params.toString())
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var ctx = document.getElementById('completionChart').getContext('2d');
            if (chartInstance) chartInstance.destroy();
            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(function (ds) {
                        return Object.assign(ds, {
                            tension: 0.3,
                            spanGaps: true,
                            pointRadius: 3,
                        });
                    })
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: data.datasets.length > 1 ? 'bottom' : 'none' },
                        tooltip: { callbacks: { label: function (ctx) { return ctx.dataset.label + ': ' + ctx.parsed.y + '%'; } } }
                    },
                    scales: {
                        y: { min: 0, max: 100, ticks: { callback: function (v) { return v + '%'; } } }
                    }
                }
            });
        });
    }

    loadChart(currentGroup);

    document.querySelectorAll('.chart-group-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('.chart-group-btn').forEach(function (b) {
                b.classList.remove('btn-secondary');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-secondary');
            loadChart(btn.dataset.group);
        });
    });
})();
</script>
@endif
@endpush

@endsection
