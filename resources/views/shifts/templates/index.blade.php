@extends('layouts.app')
@section('title', 'Templates de Escala')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-layout-text-window me-2"></i>Templates de Escala</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTemplateModal">
            <i class="bi bi-plus-lg me-1"></i>Novo template
        </button>
        <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-outline-secondary">Voltar</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
@endif

@php
$days = ['Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'];
@endphp

@forelse($templates as $tpl)
@php $tplUsers = $tpl->unit?->users ?? collect(); @endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center gap-3 py-2">
        <span class="fw-semibold">{{ $tpl->name }}</span>
        <span class="text-muted small">{{ $tpl->unit?->name ?? '—' }}</span>
        <span class="badge bg-secondary">{{ ['weekly'=>'Semanal','biweekly'=>'Quinzenal','monthly'=>'Mensal'][$tpl->period] }}</span>
        <div class="ms-auto d-flex gap-2">
            {{-- Aplicar --}}
            <button class="btn btn-sm btn-outline-primary py-0"
                    data-bs-toggle="modal" data-bs-target="#applyModal"
                    data-id="{{ $tpl->id }}"
                    data-name="{{ $tpl->name }}"
                    data-url="{{ route('shifts.templates.apply', $tpl) }}">
                <i class="bi bi-play-fill me-1"></i>Aplicar
            </button>
            {{-- Excluir --}}
            <form method="POST" action="{{ route('shifts.templates.destroy', $tpl) }}" class="d-inline"
                  onsubmit="return confirm('Excluir template {{ addslashes($tpl->name) }}?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger py-0">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Config editor --}}
    <div class="card-body">
        <form method="POST" action="{{ route('shifts.templates.update', $tpl) }}" class="tpl-form">
            @csrf @method('PUT')
            <input type="hidden" name="name"   value="{{ $tpl->name }}">
            <input type="hidden" name="period" value="{{ $tpl->period }}">
            <input type="hidden" name="config" class="config-json" value="">

            @if($tplUsers->isEmpty())
            <p class="text-muted small">Nenhum funcionário atribuído à unidade <strong>{{ $tpl->unit?->name }}</strong>.
               <a href="{{ route('admin.users.index') }}">Atribuir usuários</a>
            </p>
            @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2 config-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:120px">Dia</th>
                            <th>Funcionário</th>
                            <th style="width:100px">Entrada</th>
                            <th style="width:100px">Saída</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody class="config-rows">
                        @foreach($tpl->config ?? [] as $entry)
                        <tr>
                            <td>
                                <select class="form-select form-select-sm row-day">
                                    @foreach($days as $i => $d)
                                    <option value="{{ $i }}" {{ ($entry['day_of_week'] ?? 0) == $i ? 'selected' : '' }}>{{ $d }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm row-user">
                                    @foreach($tplUsers as $u)
                                    <option value="{{ $u->id }}" {{ ($entry['user_id'] ?? 0) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="time" class="form-control form-control-sm row-start" value="{{ $entry['start_time'] ?? '08:00' }}"></td>
                            <td><input type="time" class="form-control form-control-sm row-end"   value="{{ $entry['end_time']   ?? '17:00' }}"></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 btn-remove-row">
                                    <i class="bi bi-x"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-add-row"
                        data-tpl="{{ $tpl->id }}"
                        data-users="{{ json_encode($tplUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name])) }}">
                    <i class="bi bi-plus me-1"></i>Adicionar linha
                </button>
                <button type="submit" class="btn btn-sm btn-primary btn-save-config">
                    <i class="bi bi-floppy me-1"></i>Salvar escala
                </button>
            </div>
            @endif
        </form>
    </div>
</div>
@empty
<div class="card border-0 shadow-sm">
    <div class="text-muted text-center py-5">
        <i class="bi bi-layout-text-window fs-3 d-block mb-2"></i>
        Nenhum template criado. Crie um para reutilizar escalas semanais.
    </div>
</div>
@endforelse

{{-- Modal: Novo template --}}
<div class="modal fade" id="newTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('shifts.templates.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Novo template</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Nome <span class="text-danger">*</span></label>
                        <input name="name" class="form-control form-control-sm" required placeholder="ex: Escala padrão loja centro">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Unidade <span class="text-danger">*</span></label>
                        <select name="unit_id" class="form-select form-select-sm" required>
                            <option value="">— selecione —</option>
                            @foreach($units as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label form-label-sm">Repetição</label>
                        <select name="period" class="form-select form-select-sm">
                            <option value="weekly">Semanal (aplica 1 semana)</option>
                            <option value="biweekly">Quinzenal (aplica 2 semanas)</option>
                            <option value="monthly">Mensal (aplica 4 semanas)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Criar</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Aplicar template --}}
<div class="modal fade" id="applyModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="applyForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="applyTitle">Aplicar template</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2" id="applyDesc"></p>
                    <label class="form-label form-label-sm">Início da semana <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control form-control-sm" required
                           value="{{ \Carbon\Carbon::today()->startOfWeek()->toDateString() }}">
                    <div class="form-text">Deve ser uma segunda-feira para o template alinhar corretamente.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var days = ['Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'];

    // Modal aplicar
    document.getElementById('applyModal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        document.getElementById('applyTitle').textContent = 'Aplicar: ' + btn.dataset.name;
        document.getElementById('applyForm').action = btn.dataset.url;
    });

    // Remover linha de config
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-row')) {
            e.target.closest('tr').remove();
        }
    });

    // Adicionar linha de config
    document.querySelectorAll('.btn-add-row').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var users  = JSON.parse(btn.dataset.users);
            var tbody  = btn.closest('.card-body').querySelector('.config-rows');

            var dayOpts  = days.map(function (d, i) { return '<option value="' + i + '">' + d + '</option>'; }).join('');
            var userOpts = users.map(function (u) { return '<option value="' + u.id + '">' + u.name + '</option>'; }).join('');

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><select class="form-select form-select-sm row-day">' + dayOpts + '</select></td>' +
                '<td><select class="form-select form-select-sm row-user">' + userOpts + '</select></td>' +
                '<td><input type="time" class="form-control form-control-sm row-start" value="08:00"></td>' +
                '<td><input type="time" class="form-control form-control-sm row-end" value="17:00"></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger py-0 btn-remove-row"><i class="bi bi-x"></i></button></td>';
            tbody.appendChild(tr);
        });
    });

    // Submit: serializa linhas para JSON
    document.querySelectorAll('.tpl-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var rows  = form.querySelectorAll('.config-rows tr');
            var config = Array.from(rows).map(function (tr) {
                return {
                    day_of_week: parseInt(tr.querySelector('.row-day').value),
                    user_id:     parseInt(tr.querySelector('.row-user').value),
                    start_time:  tr.querySelector('.row-start').value,
                    end_time:    tr.querySelector('.row-end').value,
                };
            });
            form.querySelector('.config-json').value = JSON.stringify(config);
        });
    });
})();
</script>
@endpush
@endsection
