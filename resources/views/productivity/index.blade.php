@extends('layouts.app')
@section('title', 'Produtividade')

@push('styles')
<style>
.kpi-card { border-left: 4px solid; }
.kpi-card.kpi-presence  { border-color: #198754; }
.kpi-card.kpi-tasks     { border-color: #0d6efd; }
.kpi-card.kpi-purchases { border-color: #fd7e14; }
.kpi-card.kpi-overtime  { border-color: #6f42c1; }

.sort-th { cursor: pointer; user-select: none; white-space: nowrap; }
.sort-th:hover { background: #f0f4ff; }
.sort-th .sort-icon { font-size: .7rem; opacity: .4; margin-left: 3px; }
.sort-th.asc .sort-icon::after  { content: '▲'; opacity: 1; }
.sort-th.desc .sort-icon::after { content: '▼'; opacity: 1; }
.sort-th:not(.asc):not(.desc) .sort-icon::after { content: '⇅'; }

.perf-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.delta-pos { color: #198754; font-weight: 600; }
.delta-neg { color: #dc3545; font-weight: 600; }
.delta-neu { color: #6c757d; }
</style>
@endpush

@section('content')

@php
    $isToday  = !$isRange && $start->isToday();
    $isMonth  = !$isRange && $start->isSameDay($start->copy()->startOfMonth()) && $end->isSameDay($start->copy()->endOfMonth());
    $isCurMon = $isMonth && $start->isSameMonth(now());
@endphp

{{-- ── Cabeçalho ─────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Produtividade da Equipe</h4>
        <span class="text-muted small">{{ auth()->user()->company?->name }}</span>
    </div>
    <span class="text-muted small">
        @if($isRange)
            {{ $start->format('d/m/Y') }} — {{ $end->format('d/m/Y') }}
            <span class="badge bg-secondary ms-1">{{ $days }} {{ $days === 1 ? 'dia' : 'dias' }}</span>
        @elseif($isToday)
            {{ $start->format('d/m/Y') }} <span class="badge bg-primary ms-1">hoje</span>
        @elseif($isMonth)
            {{ $start->translatedFormat('F Y') }}
            @if($isCurMon)<span class="badge bg-secondary ms-1">mês atual</span>@endif
        @else
            {{ $start->format('d/m/Y') }}
        @endif
    </span>
</div>

{{-- ── Filtros ───────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('productivity.index') }}" class="row g-2 align-items-end flex-wrap">

            {{-- Atalhos rápidos --}}
            <div class="col-auto d-flex gap-1">
                <a href="{{ route('productivity.index', array_filter(['unit_id' => $unitId])) }}"
                   class="btn btn-sm {{ $isCurMon && !$unitId ? 'btn-primary' : 'btn-outline-primary' }}">
                    Este mês
                </a>
                <a href="{{ route('productivity.index', array_filter(['date' => now()->toDateString(), 'unit_id' => $unitId])) }}"
                   class="btn btn-sm {{ $isToday ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Hoje
                </a>
            </div>

            {{-- Data única --}}
            <div class="col-auto d-flex gap-1 align-items-end">
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Data única</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="{{ (!$isRange && !$isCurMon) ? $start->toDateString() : '' }}"
                           max="{{ now()->toDateString() }}">
                </div>
                <button type="submit" name="_mode" value="date" class="btn btn-sm btn-outline-secondary">Ver</button>
            </div>

            <div class="col-auto text-muted small px-1 align-self-end pb-1">ou</div>

            {{-- Range --}}
            <div class="col-auto d-flex gap-1 align-items-end">
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">De</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ $isRange ? $start->toDateString() : '' }}"
                           max="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Até</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ $isRange ? $end->toDateString() : '' }}"
                           max="{{ now()->toDateString() }}">
                </div>
                <button type="submit" name="_mode" value="range" class="btn btn-sm btn-outline-secondary">Ver período</button>
            </div>

            {{-- Mês --}}
            <div class="col-auto d-flex gap-1 align-items-end">
                <div>
                    <label class="form-label form-label-sm mb-1 text-muted">Mês</label>
                    <input type="month" name="month_pick" id="monthPick" class="form-control form-control-sm"
                           value="{{ $isMonth ? $start->format('Y-m') : '' }}">
                </div>
                <button type="button" id="btnMonth" class="btn btn-sm btn-outline-secondary">Ver mês</button>
            </div>

            @if($units->count() > 1)
            <div class="col-auto align-self-end">
                <select name="unit_id" class="form-select form-select-sm" style="min-width:160px">
                    <option value="">Todas as unidades</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ $unitId == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

        </form>
    </div>
</div>

{{-- ── KPI Cards ─────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm kpi-card kpi-presence h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-calendar-check me-1"></i>Presença média</div>
                <div class="fs-3 fw-bold text-success">
                    {{ $kpi['avg_presence'] !== null ? $kpi['avg_presence'].'%' : '—' }}
                </div>
                <div class="text-muted" style="font-size:.75rem">dias com ponto / dias escalados</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm kpi-card kpi-tasks h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-check2-square me-1"></i>Tarefas concluídas</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($kpi['total_tasks']) }}</div>
                <div class="text-muted" style="font-size:.75rem">no período pela equipe</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm kpi-card kpi-purchases h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-cart-plus me-1"></i>Compras solicitadas</div>
                <div class="fs-3 fw-bold" style="color:#fd7e14">{{ number_format($kpi['total_purchases']) }}</div>
                <div class="text-muted" style="font-size:.75rem">itens no período</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm kpi-card kpi-overtime h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-clock-history me-1"></i>Horas extras acum.</div>
                <div class="fs-3 fw-bold" style="color:#6f42c1">{{ $kpi['total_overtime_h'] }}h</div>
                <div class="text-muted" style="font-size:.75rem">
                    {{ $kpi['total_worked_h'] }}h trabalhadas /
                    {{ $kpi['total_scheduled_h'] }}h escaladas
                </div>
            </div>
        </div>
    </div>
</div>

@if($employees->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-people fs-1 d-block mb-2"></i>
            Nenhum funcionário encontrado para os filtros selecionados.
        </div>
    </div>
@else

<div class="row g-3">

    {{-- ── Ranking ─────────────────────────────────────────── --}}
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="bi bi-bar-chart-steps me-2 text-primary"></i>Ranking da Equipe</span>
                <span class="text-muted small">{{ $employees->count() }} funcionários · clique no cabeçalho para ordenar</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" id="rankingTable">
                    <thead class="table-light" style="font-size:.8rem">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Funcionário</th>
                            <th class="sort-th text-center" data-col="tasks">Tarefas<span class="sort-icon"></span></th>
                            <th class="sort-th text-center" data-col="purchases">Compras<span class="sort-icon"></span></th>
                            <th class="sort-th text-center" data-col="presence">Presença<span class="sort-icon"></span></th>
                            <th class="sort-th text-center" data-col="scheduled">Escalado<span class="sort-icon"></span></th>
                            <th class="sort-th text-center" data-col="worked">Trabalhado<span class="sort-icon"></span></th>
                            <th class="sort-th text-center" data-col="delta">Δ Horas<span class="sort-icon"></span></th>
                        </tr>
                    </thead>
                    <tbody id="rankingBody">
                        @foreach($employees as $e)
                        @php
                            $presence = $e['presence_rate'];
                            $dotColor = match(true) {
                                $presence === null => '#adb5bd',
                                $presence >= 90   => '#198754',
                                $presence >= 70   => '#fd7e14',
                                default           => '#dc3545',
                            };
                            $deltaMins = $e['delta_minutes'];
                            $deltaClass = $deltaMins > 0 ? 'delta-pos' : ($deltaMins < 0 ? 'delta-neg' : 'delta-neu');
                            $deltaSign  = $deltaMins > 0 ? '+' : '';
                            $deltaH     = intdiv(abs($deltaMins), 60);
                            $deltaM     = abs($deltaMins) % 60;
                            $deltaStr   = ($deltaMins < 0 ? '-' : $deltaSign) . $deltaH . 'h' . ($deltaM > 0 ? ' '.$deltaM.'min' : '');
                        @endphp
                        <tr
                            data-tasks="{{ $e['tasks_done'] }}"
                            data-purchases="{{ $e['purchase_count'] }}"
                            data-presence="{{ $presence ?? -1 }}"
                            data-scheduled="{{ $e['scheduled_hours'] }}"
                            data-worked="{{ $e['worked_hours'] }}"
                            data-delta="{{ $deltaMins }}"
                        >
                            <td class="ps-3 text-muted small rank-num">—</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="perf-dot" style="background:{{ $dotColor }}" title="Presença {{ $presence !== null ? $presence.'%' : 'sem escala' }}"></span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.88rem">{{ $e['user']->name }}</div>
                                        <div class="text-muted" style="font-size:.72rem">{{ ucfirst($e['user']->role) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center fw-semibold">{{ $e['tasks_done'] ?: '—' }}</td>
                            <td class="text-center">{{ $e['purchase_count'] ?: '—' }}</td>
                            <td class="text-center">
                                @if($presence !== null)
                                    <span class="{{ $presence >= 90 ? 'text-success' : ($presence >= 70 ? 'text-warning' : 'text-danger') }} fw-semibold">
                                        {{ $presence }}%
                                    </span>
                                    <div class="text-muted" style="font-size:.7rem">{{ $e['clock_days'] }}/{{ $e['scheduled_days'] }} dias</div>
                                @else
                                    <span class="text-muted">sem escala</span>
                                @endif
                            </td>
                            <td class="text-center text-muted">{{ $e['scheduled_hours'] > 0 ? $e['scheduled_hours'].'h' : '—' }}</td>
                            <td class="text-center">{{ $e['worked_hours'] > 0 ? $e['worked_hours'].'h' : '—' }}</td>
                            <td class="text-center {{ $deltaClass }}">
                                {{ $deltaMins !== 0 ? $deltaStr : ($e['worked_hours'] > 0 ? '<span class="text-muted">0h</span>' : '—') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Scatter Plot ────────────────────────────────────── --}}
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="bi bi-graph-up me-2 text-success"></i>Escala × Ponto</span>
                <div class="text-muted" style="font-size:.73rem">acima da linha = horas extras · abaixo = faltou horas</div>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center p-3">
                <canvas id="scatterChart" style="max-height:320px"></canvas>
            </div>
        </div>
    </div>

</div>

{{-- ── Legenda de cores ────────────────────────────────────── --}}
<div class="d-flex gap-3 mt-3 flex-wrap" style="font-size:.78rem">
    <span><span class="perf-dot me-1" style="background:#198754;display:inline-block"></span>Presença ≥ 90%</span>
    <span><span class="perf-dot me-1" style="background:#fd7e14;display:inline-block"></span>70–89%</span>
    <span><span class="perf-dot me-1" style="background:#dc3545;display:inline-block"></span>< 70%</span>
    <span><span class="perf-dot me-1" style="background:#adb5bd;display:inline-block"></span>Sem escala</span>
</div>

@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ── Mês picker → redireciona com date_from/date_to do mês ──
document.getElementById('btnMonth')?.addEventListener('click', function () {
    const val = document.getElementById('monthPick').value;
    if (!val) return;
    const [y, m] = val.split('-').map(Number);
    const from = new Date(y, m - 1, 1);
    const to   = new Date(y, m, 0);
    const fmt  = d => d.toISOString().slice(0, 10);
    const url  = new URL(window.location.href);
    url.searchParams.set('date_from', fmt(from));
    url.searchParams.set('date_to',   fmt(to));
    url.searchParams.delete('date');
    url.searchParams.delete('month_pick');
    window.location.href = url.toString();
});

// ── Scatter chart ────────────────────────────────────────
@php
    $scatterData = $employees->map(fn($e) => [
        'x'     => $e['scheduled_hours'],
        'y'     => $e['worked_hours'],
        'label' => $e['user']->name,
    ])->values();
    $maxH = max(
        $employees->max('scheduled_hours') ?? 0,
        $employees->max('worked_hours') ?? 0,
        1
    );
@endphp

(function () {
    const ctx = document.getElementById('scatterChart');
    if (!ctx) return;

    const data = @json($scatterData);
    const maxH = {{ ceil($maxH * 1.1) }};

    new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Funcionários',
                data: data.map(d => ({ x: d.x, y: d.y })),
                backgroundColor: data.map(d => {
                    if (d.y > d.x) return 'rgba(111,66,193,.75)';
                    if (d.y < d.x) return 'rgba(220,53,69,.75)';
                    return 'rgba(25,135,84,.75)';
                }),
                pointRadius: 7,
                pointHoverRadius: 9,
            }, {
                label: 'Ideal',
                data: [{ x: 0, y: 0 }, { x: maxH, y: maxH }],
                type: 'line',
                borderColor: 'rgba(0,0,0,.18)',
                borderDash: [6, 4],
                borderWidth: 1.5,
                pointRadius: 0,
                fill: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            if (ctx.datasetIndex === 1) return null;
                            const d = data[ctx.dataIndex];
                            return `${d.label}: ${d.x}h escalado / ${d.y}h trabalhado`;
                        }
                    }
                }
            },
            scales: {
                x: { title: { display: true, text: 'Horas escaladas', font: { size: 11 } }, min: 0, max: maxH },
                y: { title: { display: true, text: 'Horas trabalhadas', font: { size: 11 } }, min: 0, max: maxH },
            }
        }
    });
})();

// ── Sorting ──────────────────────────────────────────────
(function () {
    const headers = document.querySelectorAll('.sort-th');
    let currentCol = null, currentDir = 'desc';

    headers.forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.col;
            if (currentCol === col) {
                currentDir = currentDir === 'desc' ? 'asc' : 'desc';
            } else {
                currentCol = col;
                currentDir = 'desc';
            }

            headers.forEach(h => h.classList.remove('asc', 'desc'));
            th.classList.add(currentDir);

            const body = document.getElementById('rankingBody');
            const rows = Array.from(body.querySelectorAll('tr'));

            rows.sort((a, b) => {
                const va = parseFloat(a.dataset[col]);
                const vb = parseFloat(b.dataset[col]);
                return currentDir === 'desc' ? vb - va : va - vb;
            });

            rows.forEach((row, i) => {
                row.querySelector('.rank-num').textContent = i + 1;
                body.appendChild(row);
            });
        });
    });

    document.querySelectorAll('#rankingBody tr').forEach((row, i) => {
        row.querySelector('.rank-num').textContent = i + 1;
    });
})();
</script>
@endpush
