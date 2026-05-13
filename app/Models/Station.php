<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $fillable = ['company_id', 'name', 'color', 'order', 'active'];

    protected $casts = ['active' => 'boolean', 'order' => 'integer'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function shifts()  { return $this->hasMany(Shift::class); }
}
