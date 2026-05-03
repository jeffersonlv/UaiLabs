<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div style="display:flex; align-items:center; margin-bottom:1rem;">
            <label for="login" style="width:110px; text-align:right; padding-right:0.75rem; font-size:0.875rem; font-weight:600; color:#374151; flex-shrink:0;">
                Usuário
            </label>
            <div style="flex:1;">
                <input id="login" type="text" name="login" value="{{ old('login') }}"
                       required autofocus autocomplete="username"
                       placeholder="e-mail ou usuário"
                       style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.875rem; outline:none; box-sizing:border-box;">
                @error('login')
                    <p style="color:#dc2626; font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div style="display:flex; align-items:center; margin-bottom:1rem;">
            <label for="password" style="width:110px; text-align:right; padding-right:0.75rem; font-size:0.875rem; font-weight:600; color:#374151; flex-shrink:0;">
                Senha
            </label>
            <div style="flex:1;">
                <input id="password" type="password" name="password"
                       required autocomplete="current-password"
                       style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.875rem; outline:none; box-sizing:border-box;">
                @error('password')
                    <p style="color:#dc2626; font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div style="display:flex; align-items:center; margin-bottom:1.5rem; padding-left:110px;">
            <input id="remember_me" type="checkbox" name="remember"
                   style="margin-right:0.5rem; cursor:pointer;">
            <label for="remember_me" style="font-size:0.875rem; color:#6b7280; cursor:pointer;">
                Lembrar-me
            </label>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; padding-left:110px;">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   style="font-size:0.8rem; color:#64748b; text-decoration:underline;">
                    Esqueci a senha
                </a>
            @endif
            <x-primary-button>Entrar</x-primary-button>
        </div>
    </form>
</x-guest-layout>
