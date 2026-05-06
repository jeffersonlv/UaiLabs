<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['company_id', 'name', 'type', 'address', 'active'];

    const TYPES = [
        'matriz'      => 'Matriz',
        'filial'      => 'Filial',
        'quiosque'    => 'Quiosque',
        'dark_kitchen'=> 'Dark Kitchen',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function users() { return $this->belongsToMany(User::class, 'user_units'); }
    public function activities() { return $this->hasMany(Activity::class); }
}
