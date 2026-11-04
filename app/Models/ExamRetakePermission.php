<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamRetakePermission extends Model
{
    protected $fillable = [
        'exam_id', 'student_id', 'enabled_by_lecturer_id', 'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function exam(){ return $this->belongsTo(Exam::class); }
    public function student(){ return $this->belongsTo(User::class, 'student_id'); }
    public function lecturer(){ return $this->belongsTo(User::class, 'enabled_by_lecturer_id'); }
}
