<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportRequestNote extends Model
{
    protected $fillable = ['support_request_id', 'user_id', 'body', 'intensity'];

    public function user()           { return $this->belongsTo(User::class); }
    public function supportRequest() { return $this->belongsTo(SupportRequest::class); }
}
