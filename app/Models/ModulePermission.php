<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModulePermission extends Model
{
    protected $fillable = ['company_id', 'role', 'module_key', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class); }
}
