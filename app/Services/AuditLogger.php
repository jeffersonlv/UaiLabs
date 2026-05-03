<?php
namespace App\Services;

use App\Models\AuditLog;
use App\Models\TaskOccurrence;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(
        string $action,
        string $entity,
        ?int $entityId = null,
        array $details = []
    ): void {
        $user = auth()->user();

        AuditLog::create([
            'company_id' => $user?->company_id,
            'user_id'    => $user?->id,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entityId,
            'details'    => $details ?: null,
        ]);
    }

    public static function taskComplete(TaskOccurrence $occurrence): void
    {
        self::log('task.complete', 'task_occurrence', $occurrence->id, [
            'atividade' => $occurrence->activity->title,
            'periodo'   => $occurrence->period_start,
        ]);
    }

    public static function taskReopen(TaskOccurrence $occurrence, string $justification): void
    {
        self::log('task.reopen', 'task_occurrence', $occurrence->id, [
            'atividade'     => $occurrence->activity->title,
            'periodo'       => $occurrence->period_start,
            'justificativa' => $justification,
        ]);
    }

    public static function login(): void
    {
        self::log('login', 'user', auth()->id(), [
            'ip' => request()->ip(),
        ]);
    }

    public static function logout(): void
    {
        self::log('logout', 'user', auth()->id(), [
            'ip' => request()->ip(),
        ]);
    }
}
