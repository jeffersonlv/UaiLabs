<?php

namespace App\Models;

use App\Scopes\TenantScope;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'category_id', 'subcategory_id', 'title', 'description',
        'periodicity', 'sequence_required', 'sequence_order', 'active', 'created_by',
    ];

    protected $casts = ['sequence_required' => 'boolean', 'active' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company()     { return $this->belongsTo(Company::class); }
    public function category()    { return $this->belongsTo(Category::class); }
    public function subcategory() { return $this->belongsTo(Subcategory::class); }
    public function units()       { return $this->belongsToMany(Unit::class, 'activity_units'); }
    public function occurrences() { return $this->hasMany(TaskOccurrence::class); }
    public function creator()     { return $this->belongsTo(User::class, 'created_by'); }

    /** Returns true if activity is "Geral" (no unit assignment). */
    public function isGeral(): bool
    {
        return ! $this->units()->exists();
    }
}