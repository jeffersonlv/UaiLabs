<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskOccurrenceLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['task_occurrence_id', 'user_id', 'action', 'justification', 'done_at'];

    protected $casts = ['done_at' => 'datetime'];

    public function user()      { return $this->belongsTo(User::class); }
    public function occurrence() { return $this->belongsTo(TaskOccurrence::class, 'task_occurrence_id'); }
}
