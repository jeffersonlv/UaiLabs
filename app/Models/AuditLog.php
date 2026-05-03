<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'user_id', 'action', 'entity', 'entity_id', 'details', 'timestamp',
    ];

    protected $casts = [
        'details'   => 'array',
        'timestamp' => 'datetime',
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }
}
