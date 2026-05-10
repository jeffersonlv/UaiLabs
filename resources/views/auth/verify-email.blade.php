@extends('layouts.guest')
@section('content')
<p class="text-muted small mb-4">Antes de começar, verifique seu e-mail clicando no link que enviamos. Se não recebeu, podemos reenviar.</p>

@if (session('status') == 'verification-link-sent')
    <div class="alert alert-success py-2 mb-3" style="font-size:.85rem">
        Um novo link de verificação foi enviado para o seu e-mail.
    </div>
@endif

<form method="POST" action="{{ route('verification.send') }}" class="d-grid mb-3">
    @csrf
    <button type="submit" class="btn btn-dark">
        <i class="bi bi-envelope me-1"></i>Reenviar e-mail de verificação
    </button>
</form>

<form method="POST" action="{{ route('logout') }}" class="text-center">
    @csrf
    <button type="submit" class="btn btn-link p-0" style="font-size:.8rem;color:#64748b">Sair</button>
</form>
@endsection
