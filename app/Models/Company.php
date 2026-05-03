<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'slug', 'email', 'phone', 'active'];

    public function units() { return $this->hasMany(Unit::class); }
    public function users() { return $this->hasMany(User::class); }
    public function categories() { return $this->hasMany(Category::class); }
    public function activities() { return $this->hasMany(Activity::class); }
}
