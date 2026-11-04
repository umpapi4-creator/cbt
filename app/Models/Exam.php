<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Exam extends Model {
 protected $fillable=['lecturer_id','course_id','classroom_id','title','description','starts_at','ends_at','duration_minutes','token','passing_score','shuffle_questions','shuffle_options','show_result','status'];
 protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime','shuffle_questions'=>'boolean','shuffle_options'=>'boolean','show_result'=>'boolean'];
 public function lecturer(){return $this->belongsTo(User::class,'lecturer_id');}
 public function course(){return $this->belongsTo(Course::class);}
 public function classroom(){return $this->belongsTo(Classroom::class);}
 public function questions(){return $this->hasMany(Question::class)->orderBy('id');}
 public function attempts(){return $this->hasMany(ExamAttempt::class);}
 public function retakePermissions(){return $this->hasMany(ExamRetakePermission::class);}
}
