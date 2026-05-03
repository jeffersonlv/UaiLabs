@extends('layouts.app')
@section('content')

@php
    $isClosed  = $supportRequest->isClosed();
    $authUser  = auth()->user();
@endphp

<div class="d-flex align-items-center mb-4">
    <a href="{{ route('support-requests.index') }}" class="btn btn-sm btn-outline-secondary me-3">&#8592; Voltar</a>
    <h4 class="mb-0 me-3">{{ $supportRequest->title }}</h4>
    <span class="badge {{ \App\Models\SupportRequest::statusBadge($supportRequest->status) }}">
        {{ \App\Models\SupportRequest::statusLabel($supportRequest->status) }}
    </span>
    @if($supportRequest->important)
        <span class="text-warning fs-5 ms-2">&#9733;</span>
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
                <div class="d-flex align-items-center mb-2">
                    <span class="fw-semibold me-2">{{ $supportRequest->user->name ?? '—' }}</span>
                    <span class="text-muted small">{{ $supportRequest->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div style="white-space:pre-wrap; line-height:1.7">{{ $supportRequest->body }}</div>
            </div>
        </div>

        {{-- Thread de notas --}}
        @if($supportRequest->notes->isNotEmpty())
        <div class="mb-4">
            <h6 class="text-muted mb-3">Notas ({{ $supportRequest->notes->count() }})</h6>
            @foreach($supportRequest->notes as $note)
            @php $isMine = $note->user_id === $authUser->id; @endphp
            <div class="d-flex mb-3 {{ $isMine ? 'flex-row-reverse' : '' }}">
                {{-- Avatar --}}
                <div class="rounded-circle bg-{{ $isMine ? 'primary' : 'secondary' }} text-white d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:36px;height:36px;font-size:.85rem;font-weight:600">
                    {{ strtoupper(substr($note->user->name ?? '?', 0, 1)) }}
                </div>
                {{-- Balão --}}
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
                    <br>Feedback: <span class="fs-4">{{ \App\Models\SupportRequest::faceEmoji($supportRequest->feedback) }}</span>
                @endif
            </div>
        </div>
        @else

        {{-- Formulário de nova nota --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold small">Adicionar nota</div>
            <div class="card-body">
                <form method="POST" action="{{ route('support-requests.notes.store', $supportRequest) }}">
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
                <form method="POST" action="{{ route('support-requests.close', $supportRequest) }}">
                    @csrf @method('PATCH')
                    @include('support-requests._face_picker', ['fieldName' => 'feedback', 'label' => 'Como foi o atendimento? (opcional)'])
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Encerrar esta solicitação? Não será possível adicionar notas depois.')">
                            &#128274; Marcar como concluída
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @endif
    </div>

    {{-- ── Coluna lateral ───────────────────────────── --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold small">Informações</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Prioridade:</span>
                    @if($supportRequest->priority)
                        <span class="badge {{ \App\Models\SupportRequest::priorityBadge($supportRequest->priority) }} ms-1">
                            {{ \App\Models\SupportRequest::priorityLabel($supportRequest->priority) }}
                        </span>
                    @else <span class="ms-1 text-muted">—</span> @endif
                </div>
                <div class="mb-2"><span class="text-muted">Aberta em:</span>
                    <span class="ms-1">{{ $supportRequest->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="mb-2"><span class="text-muted">Notas:</span>
                    <span class="ms-1">{{ $supportRequest->notes->count() }}</span>
                </div>
                @if($supportRequest->superadmin_note)
                <hr>
                <div class="text-muted mb-1">Resposta do suporte:</div>
                <div class="fst-italic">{{ $supportRequest->superadmin_note }}</div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
