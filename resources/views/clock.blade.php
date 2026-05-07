@extends('layouts.app')
@section('title', 'Bater Ponto')
@section('content')
<div class="d-flex justify-content-center mt-4">
    <div class="card border-0 shadow-sm p-4" style="max-width:400px;width:100%">
        <h4 class="text-center mb-4"><i class="bi bi-clock-history me-2"></i>Bater Ponto</h4>

        @if(session('clock_message'))
            <div class="alert alert-{{ session('clock_type') === 'clock_in' ? 'success' : 'info' }} text-center">
                <i class="bi bi-check-circle me-2"></i>{{ session('clock_message') }}
            </div>
        @endif

        <form method="POST" action="{{ route('clock.punch') }}">
            @csrf
            @if(!$authUser)
                <div class="mb-3">
                    <label class="form-label">Usuário</label>
                    <input name="username" class="form-control" autocomplete="username" required
                           value="{{ old('username') }}">
                    @error('username') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            @else
                <p class="text-center text-muted mb-3">Olá, <strong>{{ $authUser->name }}</strong></p>
            @endif

            @if($units && $units->count() > 1)
            <div class="mb-3">
                <label class="form-label">Unidade</label>
                <select name="unit_id" class="form-select" required>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @elseif($units && $units->count() === 1)
                <input type="hidden" name="unit_id" value="{{ $units->first()->id }}">
            @else
                <div class="mb-3">
                    <label class="form-label">ID da Unidade</label>
                    <input type="number" name="unit_id" class="form-control" required>
                </div>
            @endif

            <div class="mb-4">
                <label class="form-label">PIN</label>
                <input type="password" name="pin" class="form-control text-center"
                       style="letter-spacing:.3em;font-size:1.4rem" maxlength="6"
                       inputmode="numeric" autocomplete="current-password" required>
                @error('pin') <div class="text-danger small mt-1 text-center">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">
                <i class="bi bi-clock me-2"></i>Registrar
            </button>
        </form>

        @if($authUser)
        <div class="text-center mt-3">
            <a href="{{ route('time-entries.dashboard') }}" class="small text-muted">Ver meu histórico</a>
        </div>
        @endif
    </div>
</div>
@endsection