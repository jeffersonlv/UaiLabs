<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['company_id', 'name', 'address', 'active'];

    public function company() { return $this->belongsTo(Company::class); }
    public function users() { return $this->belongsToMany(User::class, 'user_units'); }
    public function activities() { return $this->hasMany(Activity::class); }
}
