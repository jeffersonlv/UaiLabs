<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'company_id', 'unit_id', 'user_id',
        'product_name', 'quantity_text',
        'notes', 'status', 'status_changed_by', 'status_changed_at', 'cancellation_reason',
    ];

    protected $casts = ['status_changed_at' => 'datetime'];

    const STATUSES = [
        'requested' => ['label' => 'Solicitado', 'color' => 'warning'],
        'ordered'   => ['label' => 'Pedido',      'color' => 'info'],
        'purchased' => ['label' => 'Comprado',    'color' => 'success'],
        'cancelled' => ['label' => 'Cancelado',   'color' => 'secondary'],
    ];

    const CANCEL_REASONS = [
        'ja_comprado'     => 'Já foi comprado',
        'nao_necessario'  => 'Não é mais necessário',
        'personalizado'   => 'Outro motivo',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company()       { return $this->belongsTo(Company::class); }
    public function unit()          { return $this->belongsTo(Unit::class); }
    public function user()          { return $this->belongsTo(User::class); }
    public function statusChangedBy() { return $this->belongsTo(User::class, 'status_changed_by'); }

    public function statusLabel(): string { return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status); }
    public function statusColor(): string { return self::STATUSES[$this->status]['color'] ?? 'secondary'; }

    public function canBeCancelledBy(User $user): bool
    {
        return $this->status === 'requested';
    }
}