<?php

namespace App\Http\Requests\Auth;

use App\Services\SecurityLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();
        $this->ensureNotLockedOut();

        $login = $this->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $login, 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            $this->recordFailedAttempt();

            SecurityLogger::authFailure($login, $this->ip());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        $this->clearFailedAttempts();
        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    // A07: account lockout after 10 cumulative failures (1 hour window)
    private function ensureNotLockedOut(): void
    {
        $key = 'login_lockout:' . Str::lower($this->input('login'));
        if (Cache::get($key, 0) >= 10) {
            SecurityLogger::accountLocked($this->input('login'), $this->ip());
            throw ValidationException::withMessages([
                'login' => 'Conta bloqueada por excesso de tentativas. Tente novamente em 1 hora.',
            ]);
        }
    }

    private function recordFailedAttempt(): void
    {
        $key     = 'login_lockout:' . Str::lower($this->input('login'));
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addHour());
    }

    private function clearFailedAttempts(): void
    {
        Cache::forget('login_lockout:' . Str::lower($this->input('login')));
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')) . '|' . $this->ip());
    }
}
