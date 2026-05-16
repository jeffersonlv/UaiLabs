@extends('layouts.app')
@section('title', 'Alterar PIN')
@section('content')

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-shield-lock me-2 text-primary"></i>Alterar PIN</h5>
                <small class="text-muted">O PIN é usado para registrar ponto.</small>
            </div>
            <div class="card-body">

                @if(session('status') === 'pin-updated')
                <div class="alert alert-success alert-dismissible py-2">
                    PIN alterado com sucesso.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif

                @if($user->pin_reset_required)
                <div class="alert alert-warning py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Você precisa definir um novo PIN antes de continuar.
                </div>
                @endif

                <form method="POST" action="{{ route('profile.pin.update') }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Senha atual</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror"
                               autocomplete="current-password" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Novo PIN <span class="text-muted fw-normal">(4 a 6 dígitos)</span></label>
                        <input type="password" name="pin" inputmode="numeric" pattern="\d{4,6}"
                               class="form-control @error('pin') is-invalid @enderror"
                               autocomplete="new-password" required maxlength="6">
                        @error('pin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Confirmar PIN</label>
                        <input type="password" name="pin_confirmation" inputmode="numeric" pattern="\d{4,6}"
                               class="form-control" autocomplete="new-password" required maxlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2 me-1"></i>Salvar PIN
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-3 text-center">
            <a href="{{ route('password.edit') }}" class="small text-muted">
                <i class="bi bi-key me-1"></i>Alterar senha
            </a>
        </div>
    </div>
</div>

@endsection
