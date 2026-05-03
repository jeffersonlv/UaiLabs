<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportRequest extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'title', 'body',
        'status', 'important', 'priority', 'superadmin_note',
        'closed_at', 'closed_by', 'feedback',
    ];

    protected $casts = [
        'important' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function user()     { return $this->belongsTo(User::class); }
    public function company()  { return $this->belongsTo(Company::class); }
    public function closedBy() { return $this->belongsTo(User::class, 'closed_by'); }
    public function notes()    { return $this->hasMany(SupportRequestNote::class)->orderBy('created_at'); }

    public function isClosed(): bool { return ! is_null($this->closed_at); }

    // ── Faces ────────────────────────────────────────────────
    public static function faceEmoji(?int $v): string
    {
        return match($v) {
            1 => '😠', 2 => '😟', 3 => '😐', 4 => '🙂', 5 => '😄',
            default => '',
        };
    }

    public static function faceColor(?int $v): string
    {
        return match($v) {
            1 => 'text-danger',
            2 => 'text-warning',
            3 => 'text-secondary',
            4 => 'text-info',
            5 => 'text-success',
            default => '',
        };
    }

    // ── Helpers de exibição ──────────────────────────────────
    public static function priorityLabel(?int $priority): string
    {
        return match($priority) { 1 => 'Alta', 2 => 'Média', 3 => 'Baixa', default => '—' };
    }

    public static function priorityBadge(?int $priority): string
    {
        return match($priority) {
            1 => 'bg-danger', 2 => 'bg-warning text-dark', 3 => 'bg-secondary',
            default => 'bg-light text-muted border',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'avaliar' => 'Avaliar', 'fazer' => 'Fazer',
            'perguntar' => 'Perguntar', 'feito' => 'Feito',
            default => $status,
        };
    }

    public static function statusBadge(string $status): string
    {
        return match($status) {
            'avaliar' => 'bg-primary', 'fazer' => 'bg-warning text-dark',
            'perguntar' => 'bg-info text-dark', 'feito' => 'bg-success',
            default => 'bg-secondary',
        };
    }
}
