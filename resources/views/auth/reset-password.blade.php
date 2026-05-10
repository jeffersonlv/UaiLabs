@extends('layouts.guest')
@section('content')
<form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold" style="font-size:.85rem">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
               class="form-control @error('email') is-invalid @enderror"
               required autofocus autocomplete="username">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold" style="font-size:.85rem">Nova senha</label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               required autocomplete="new-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label fw-semibold" style="font-size:.85rem">Confirmar nova senha</label>
        <input id="password_confirmation" type="password" name="password_confirmation"
               class="form-control @error('password_confirmation') is-invalid @enderror"
               required autocomplete="new-password">
        @error('password_confirmation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-dark">
            <i class="bi bi-lock me-1"></i>Redefinir senha
        </button>
    </div>
</form>
@endsection
