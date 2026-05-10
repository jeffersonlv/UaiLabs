@extends('layouts.guest')
@section('content')
<p class="text-muted small mb-4">Esta é uma área segura. Por favor, confirme sua senha antes de continuar.</p>

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf

    <div class="mb-4">
        <label for="password" class="form-label fw-semibold" style="font-size:.85rem">Senha</label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               required autocomplete="current-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-dark">Confirmar</button>
    </div>
</form>
@endsection
