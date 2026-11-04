<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id','student_id','attempt_number','started_at','deadline_at','submitted_at','status',
        'question_order','option_orders','score','correct_count','wrong_count','unanswered_count',
        'tab_switch_count','logout_count','terminated_for_tab_switch','terminated_at'
    ];

    protected $casts = [
        'started_at'=>'datetime',
        'deadline_at'=>'datetime',
        'submitted_at'=>'datetime',
        'question_order'=>'array',
        'option_orders'=>'array',
        'score'=>'decimal:2',
        'attempt_number'=>'integer',
        'tab_switch_count'=>'integer',
        'logout_count'=>'integer',
        'terminated_for_tab_switch'=>'boolean',
        'terminated_at'=>'datetime',
    ];

    public function exam(){return $this->belongsTo(Exam::class);}
    public function student(){return $this->belongsTo(User::class,'student_id');}
    public function answers(){return $this->hasMany(ExamAnswer::class,'attempt_id');}
    public function tabEvents(){return $this->hasMany(ExamTabEvent::class,'attempt_id')->orderBy('occurred_at');}
}
