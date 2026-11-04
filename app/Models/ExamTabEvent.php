<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamTabEvent extends Model
{
    protected $fillable = [
        'attempt_id',
        'event_type',
        'occurred_at',
        'count_after',
        'notified_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }
}
