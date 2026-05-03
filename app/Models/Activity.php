<?php
namespace App\Models;

use App\Scopes\TenantScope;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'unit_id', 'category_id', 'title', 'description',
        'periodicity', 'sequence_required', 'sequence_order', 'active', 'created_by'
    ];

    protected $casts = ['sequence_required' => 'boolean', 'active' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function occurrences() { return $this->hasMany(TaskOccurrence::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
