<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardAllocation extends Model
{
    protected $fillable = [
        'company_id', 'unit_id', 'user_id', 'station_id', 'date', 'period', 'created_by',
    ];

    protected $casts = ['date' => 'date'];

    public function user()    { return $this->belongsTo(User::class); }
    public function station() { return $this->belongsTo(Station::class); }
    public function unit()    { return $this->belongsTo(Unit::class); }
}
