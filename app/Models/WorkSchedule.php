<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $fillable = ['company_id', 'name', 'weekly_hours', 'is_default', 'active'];

    protected $casts = ['is_default' => 'boolean', 'active' => 'boolean', 'weekly_hours' => 'decimal:2'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function users() { return $this->hasMany(User::class); }
}