<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityLogger
{
    public static function authFailure(string $login, string $ip): void
    {
        Log::channel('security')->warning('Auth failure', [
            'login' => $login,
            'ip'    => $ip,
        ]);
    }

    public static function accountLocked(string $login, string $ip): void
    {
        Log::channel('security')->error('Account locked after repeated failures', [
            'login' => $login,
            'ip'    => $ip,
        ]);
    }

    public static function forbiddenAccess(string $path, ?int $userId, string $ip): void
    {
        Log::channel('security')->warning('Forbidden access attempt', [
            'path'    => $path,
            'user_id' => $userId,
            'ip'      => $ip,
        ]);
    }

    public static function rateLimitHit(string $path, string $ip, ?int $userId = null): void
    {
        Log::channel('security')->warning('Rate limit hit', [
            'path'    => $path,
            'ip'      => $ip,
            'user_id' => $userId,
        ]);
    }
}
