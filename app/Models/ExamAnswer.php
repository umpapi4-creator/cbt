<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExamAnswer extends Model { protected $fillable=['attempt_id','question_id','selected_option_id','is_correct','answered_at']; protected $casts=['is_correct'=>'boolean','answered_at'=>'datetime']; public function attempt(){return $this->belongsTo(ExamAttempt::class,'attempt_id');} public function question(){return $this->belongsTo(Question::class);} public function selectedOption(){return $this->belongsTo(QuestionOption::class,'selected_option_id');} }
