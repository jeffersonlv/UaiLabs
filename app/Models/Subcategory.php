<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['company_id', 'category_id', 'name', 'order'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function activities() { return $this->hasMany(Activity::class); }
}