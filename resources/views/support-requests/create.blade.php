@extends('layouts.app')
@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">

        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('support-requests.index') }}" class="btn btn-sm btn-outline-secondary me-3">&#8592; Voltar</a>
            <h4 class="mb-0">Nova Solicitação</h4>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('support-requests.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                            value="{{ old('title') }}" placeholder="Resumo da solicitação" maxlength="200" autofocus>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Descrição <span class="text-danger">*</span></label>
                        <textarea name="body" rows="6"
                            class="form-control @error('body') is-invalid @enderror"
                            placeholder="Descreva sua solicitação com detalhes...">{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('support-requests.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection