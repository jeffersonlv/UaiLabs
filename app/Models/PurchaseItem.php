<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'company_id', 'unit_id', 'created_by',
        'name', 'status', 'requested_at', 'done_at', 'done_by',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'done_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function unit()      { return $this->belongsTo(Unit::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function doneBy()    { return $this->belongsTo(User::class, 'done_by'); }
}
