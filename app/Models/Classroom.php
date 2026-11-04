<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Classroom extends Model { protected $fillable=['lecturer_id','name','academic_year']; public function lecturer(){return $this->belongsTo(User::class,'lecturer_id');} public function students(){return $this->belongsToMany(User::class,'class_student','classroom_id','student_id')->withTimestamps();} public function exams(){return $this->hasMany(Exam::class);} }
