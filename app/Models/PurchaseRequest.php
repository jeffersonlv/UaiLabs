<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'company_id', 'unit_id', 'user_id',
        'product_name', 'quantity', 'unit_of_measure',
        'notes', 'status', 'status_changed_by', 'status_changed_at',
    ];

    protected $casts = ['status_changed_at' => 'datetime', 'quantity' => 'decimal:3'];

    const STATUSES = [
        'requested' => ['label' => 'Solicitado',  'color' => 'warning'],
        'ordered'   => ['label' => 'Pedido',       'color' => 'info'],
        'purchased' => ['label' => 'Comprado',     'color' => 'success'],
        'cancelled' => ['label' => 'Cancelado',    'color' => 'secondary'],
    ];

    const UNITS_OF_MEASURE = ['un' => 'Unidade', 'kg' => 'Kg', 'g' => 'g', 'l' => 'L', 'ml' => 'mL', 'cx' => 'Caixa'];

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
    public function uomLabel(): string    { return self::UNITS_OF_MEASURE[$this->unit_of_measure] ?? $this->unit_of_measure; }

    public function canBeCancelledBy(User $user): bool
    {
        if ($this->status !== 'requested') {
            return false;
        }
        return $this->user_id === $user->id || $user->isAdminOrAbove();
    }
}