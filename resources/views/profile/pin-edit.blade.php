@extends('layouts.app')
@section('title', 'PIN de Ponto')
@section('content')
<div style="max-width:400px">
    <h4 class="mb-4">Alterar PIN de Ponto</h4>
    <div class="card border-0 shadow-sm p-4">
        <p class="text-muted small mb-3">O PIN é usado para registrar ponto na tela de relógio. Deve ter entre 4 e 6 dígitos numéricos.</p>
        <form method="POST" action="{{ route('profile.pin.update') }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">Novo PIN</label>
                <input type="password" name="pin" class="form-control" maxlength="6" inputmode="numeric"
                       placeholder="4 a 6 dígitos" required>
                @error('pin') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="mb-4">
                <label class="form-label">Confirmar PIN</label>
                <input type="password" name="pin_confirmation" class="form-control" maxlength="6" inputmode="numeric" required>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar PIN</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection