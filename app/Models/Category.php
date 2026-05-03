<?php
namespace App\Models;

use App\Scopes\TenantScope;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'description', 'active'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function activities() { return $this->hasMany(Activity::class); }
}
