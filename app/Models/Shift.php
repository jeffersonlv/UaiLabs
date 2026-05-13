<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'company_id', 'unit_id', 'user_id',
        'start_at', 'end_at', 'type', 'station_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    const TYPES = [
        'work'     => ['label' => 'Trabalho',  'color' => 'primary'],
        'vacation' => ['label' => 'Férias',    'color' => 'success'],
        'leave'    => ['label' => 'Folga',     'color' => 'warning'],
        'holiday'  => ['label' => 'Feriado',   'color' => 'secondary'],
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company()   { return $this->belongsTo(Company::class); }
    public function unit()      { return $this->belongsTo(Unit::class); }
    public function user()      { return $this->belongsTo(User::class); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
    public function station()   { return $this->belongsTo(Station::class); }

    public function typeLabel(): string { return self::TYPES[$this->type]['label'] ?? ucfirst($this->type); }
    public function typeColor(): string { return self::TYPES[$this->type]['color'] ?? 'secondary'; }

    public function durationMinutes(): int
    {
        return (int) $this->start_at->diffInMinutes($this->end_at);
    }
}