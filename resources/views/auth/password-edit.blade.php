@extends('layouts.app')
@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-6 col-lg-5">

        <div class="d-flex align-items-center mb-4">
            <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary me-3">&#8592; Voltar</a>
            <h4 class="mb-0">Redefinir senha</h4>
        </div>

        @if(session('status') === 'password-updated')
            <div class="alert alert-success alert-dismissible fade show">
                Senha alterada com sucesso.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <div class="text-muted small mb-4">
                    Logado como <strong>{{ auth()->user()->name }}</strong>
                    ({{ auth()->user()->username ?? auth()->user()->email }})
                </div>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Senha atual <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" autocomplete="current-password"
                               class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif">
                        @if($errors->updatePassword->has('current_password'))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nova senha <span class="text-danger">*</span></label>
                        <input type="password" name="password" autocomplete="new-password"
                               class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif">
                        @if($errors->updatePassword->has('password'))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                        @else
                            <div class="form-text">Mínimo de 12 caracteres.</div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirmar nova senha <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                               class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif">
                        @if($errors->updatePassword->has('password_confirmation'))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                        @endif
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Alterar senha</button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>

@endsection
