@extends('layouts.app')
@section('content')

@php
    $isClosed = $supportRequest->isClosed();
    $authUser = auth()->user();
@endphp

<div class="d-flex align-items-center mb-4">
    <a href="{{ route('admin.support-requests.index') }}" class="btn btn-sm btn-outline-secondary me-3">&#8592; Voltar</a>
    <h4 class="mb-0 me-2">Solicitação #{{ $supportRequest->id }}</h4>
    <span class="badge {{ \App\Models\SupportRequest::statusBadge($supportRequest->status) }} me-1">
        {{ \App\Models\SupportRequest::statusLabel($supportRequest->status) }}
    </span>
    @if($supportRequest->important)
        <span class="text-warning fs-5 ms-1">&#9733;</span>
    @endif
    @if($isClosed)
        <span class="badge bg-dark ms-2">&#128274; Encerrada</span>
    @endif
</div>

<div class="row g-4">

    {{-- ── Coluna principal ─────────────────────────── --}}
    <div class="col-12 col-lg-8">

        {{-- Descrição original --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="mb-1">{{ $supportRequest->title }}</h5>
                <div class="text-muted small mb-3">
                    {{ $supportRequest->user->name ?? '—' }} &middot;
                    {{ $supportRequest->company->name ?? '—' }} &middot;
                    {{ $supportRequest->created_at->format('d/m/Y \à\s H:i') }}
                </div>
                <hr>
                <div style="white-space:pre-wrap;line-height:1.7">{{ $supportRequest->body }}</div>
            </div>
        </div>

        {{-- Thread de notas --}}
        @if($supportRequest->notes->isNotEmpty())
        <div class="mb-4">
            <h6 class="text-muted mb-3">Notas ({{ $supportRequest->notes->count() }})</h6>
            @foreach($supportRequest->notes as $note)
            @php $isMine = $note->user_id === $authUser->id; @endphp
            <div class="d-flex mb-3 {{ $isMine ? 'flex-row-reverse' : '' }}">
                <div class="rounded-circle bg-{{ $isMine ? 'primary' : 'secondary' }} text-white d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:36px;height:36px;font-size:.85rem;font-weight:600">
                    {{ strtoupper(substr($note->user->name ?? '?', 0, 1)) }}
                </div>
                <div class="{{ $isMine ? 'me-2' : 'ms-2' }}" style="max-width:85%">
                    <div class="d-flex align-items-baseline gap-2 mb-1 {{ $isMine ? 'flex-row-reverse' : '' }}">
                        <span class="fw-semibold small">{{ $note->user->name ?? '—' }}</span>
                        <span class="text-muted" style="font-size:.7rem">{{ $note->created_at->format('d/m/Y H:i') }}</span>
                        @if($note->intensity)
                            <span class="fs-5" title="Intensidade {{ $note->intensity }}/5">
                                {{ \App\Models\SupportRequest::faceEmoji($note->intensity) }}
                            </span>
                        @endif
                    </div>
                    <div class="card border-0 {{ $isMine ? 'bg-primary bg-opacity-10' : 'bg-light' }}">
                        <div class="card-body py-2 px-3" style="white-space:pre-wrap;line-height:1.6">{{ $note->body }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Encerrada --}}
        @if($isClosed)
        <div class="alert alert-secondary d-flex align-items-center gap-3">
            <span class="fs-3">&#128274;</span>
            <div>
                <strong>Solicitação encerrada</strong> por {{ $supportRequest->closedBy->name ?? '—' }}
                em {{ $supportRequest->closed_at->format('d/m/Y \à\s H:i') }}.
                @if($supportRequest->feedback)
                    <br>Feedback do solicitante:
                    <span class="fs-4">{{ \App\Models\SupportRequest::faceEmoji($supportRequest->feedback) }}</span>
                @endif
            </div>
        </div>
        @else

        {{-- Formulário de nova nota --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold small">Adicionar nota</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.support-requests.notes.store', $supportRequest) }}">
                    @csrf
                    <textarea name="body" rows="3" class="form-control mb-3 @error('body') is-invalid @enderror"
                        placeholder="Escreva sua nota...">{{ old('body') }}</textarea>
                    @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror

                    @include('support-requests._face_picker', ['fieldName' => 'intensity', 'label' => 'Intensidade (opcional)'])

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">Enviar nota</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Concluir --}}
        <div class="card border-danger border-opacity-25 shadow-sm">
            <div class="card-header bg-transparent text-danger fw-semibold small">Concluir solicitação</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.support-requests.close', $supportRequest) }}">
                    @csrf @method('PATCH')
                    @include('support-requests._face_picker', ['fieldName' => 'feedback', 'label' => 'Avaliação final (opcional)'])
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Encerrar esta solicitação? Não será possível adicionar notas depois.')">
                            &#128274; Concluir e encerrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @endif
    </div>

    {{-- ── Coluna lateral ───────────────────────────── --}}
    <div class="col-12 col-lg-4">

        {{-- Classificação --}}
        @if(!$isClosed)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold small">Classificação</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.support-requests.update', $supportRequest) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label text-muted small">Status</label>
                        <select name="status" class="form-select">
                            <option value="avaliar"   {{ $supportRequest->status === 'avaliar'   ? 'selected' : '' }}>&#128269; Avaliar</option>
                            <option value="fazer"     {{ $supportRequest->status === 'fazer'     ? 'selected' : '' }}>&#9881;&#65039; Fazer</option>
                            <option value="perguntar" {{ $supportRequest->status === 'perguntar' ? 'selected' : '' }}>&#10067; Perguntar</option>
                            <option value="feito"     {{ $supportRequest->status === 'feito'     ? 'selected' : '' }}>&#9989; Feito</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Prioridade</label>
                        <select name="priority" class="form-select">
                            <option value="">— sem prioridade</option>
                            <option value="1" {{ $supportRequest->priority == 1 ? 'selected' : '' }}>&#9650; Alta</option>
                            <option value="2" {{ $supportRequest->priority == 2 ? 'selected' : '' }}>&#9654; Média</option>
                            <option value="3" {{ $supportRequest->priority == 3 ? 'selected' : '' }}>&#9660; Baixa</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-sm">Atualizar</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center">
                <form method="POST" action="{{ route('admin.support-requests.important', $supportRequest) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                        class="btn w-100 btn-sm {{ $supportRequest->important ? 'btn-warning' : 'btn-outline-warning' }}">
                        {{ $supportRequest->important ? '★ Remover importância' : '☆ Marcar como importante' }}
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Resposta --}}
        @if(!$isClosed)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent small fw-semibold">Resposta / Nota interna</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.support-requests.update', $supportRequest) }}">
                    @csrf @method('PATCH')
                    <textarea name="superadmin_note" rows="4" class="form-control mb-2"
                        placeholder="Nota visível ao solicitante...">{{ old('superadmin_note', $supportRequest->superadmin_note) }}</textarea>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
        @else
        {{-- Info encerrada --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body small">
                <div class="text-muted mb-1">Encerrada por</div>
                <div class="fw-semibold">{{ $supportRequest->closedBy->name ?? '—' }}</div>
                <div class="text-muted mt-2 mb-1">Em</div>
                <div>{{ $supportRequest->closed_at->format('d/m/Y H:i') }}</div>
                @if($supportRequest->feedback)
                <div class="text-muted mt-2 mb-1">Feedback</div>
                <div class="fs-3">{{ \App\Models\SupportRequest::faceEmoji($supportRequest->feedback) }}</div>
                @endif
            </div>
        </div>
        @endif

    </div>
</div>

@endsection
