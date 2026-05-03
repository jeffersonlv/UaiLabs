<?php
namespace App\Models;

use App\Scopes\TenantScope;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class TaskOccurrence extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'unit_id', 'activity_id',
        'period_start', 'period_end', 'status',
        'completed_by', 'completed_at', 'justification'
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function activity() { return $this->belongsTo(Activity::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function completedBy() { return $this->belongsTo(User::class, 'completed_by'); }
    public function logs() { return $this->hasMany(TaskOccurrenceLog::class)->orderBy('done_at'); }
}
