<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModulePermission extends Model
{
    protected $fillable = ['user_id', 'company_id', 'module_key', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    public function user()    { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }
}
