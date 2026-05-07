<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'unit_id', 'type',
        'recorded_at', 'original_entry_id', 'justification',
        'ip_address', 'user_agent',
    ];

    protected $casts = ['recorded_at' => 'datetime'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company()       { return $this->belongsTo(Company::class); }
    public function user()          { return $this->belongsTo(User::class); }
    public function unit()          { return $this->belongsTo(Unit::class); }
    public function originalEntry() { return $this->belongsTo(TimeEntry::class, 'original_entry_id'); }
    public function corrections()   { return $this->hasMany(TimeEntry::class, 'original_entry_id'); }
}