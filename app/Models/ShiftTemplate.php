<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class ShiftTemplate extends Model
{
    protected $fillable = ['company_id', 'unit_id', 'name', 'period', 'config'];

    protected $casts = ['config' => 'array'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function unit()    { return $this->belongsTo(Unit::class); }
}