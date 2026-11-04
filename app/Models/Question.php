<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Question extends Model {
 protected $fillable=['lecturer_id','course_id','exam_id','text','image','explanation','difficulty'];
 public function lecturer(){return $this->belongsTo(User::class,'lecturer_id');}
 public function course(){return $this->belongsTo(Course::class);}
 public function exam(){return $this->belongsTo(Exam::class);}
 public function options(){return $this->hasMany(QuestionOption::class)->orderBy('label');}
}
