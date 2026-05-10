@extends('layouts.guest')
@section('content')
<p class="text-muted small mb-4">Esqueceu a senha? Informe seu e-mail e enviaremos um link para criar uma nova.</p>

@if (session('status'))
    <div class="alert alert-success py-2 mb-3" style="font-size:.85rem">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label fw-semibold" style="font-size:.85rem">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror"
               required autofocus autocomplete="email">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-dark">
            <i class="bi bi-envelope me-1"></i>Enviar link de redefinição
        </button>
    </div>
    <div class="text-center">
        <a href="{{ route('login') }}" style="font-size:.8rem;color:#64748b">Voltar ao login</a>
    </div>
</form>
@endsection
