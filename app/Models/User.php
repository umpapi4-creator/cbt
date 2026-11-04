<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
 use HasFactory, Notifiable;
 protected $fillable=['name','username','email','password','role','student_number','lecturer_number','created_by_lecturer_id','profile_photo_path'];
 protected $hidden=['password','remember_token'];
 protected $casts=['email_verified_at'=>'datetime','password'=>'hashed'];
 public function courses(){ return $this->hasMany(Course::class,'lecturer_id'); }
 public function questions(){ return $this->hasMany(Question::class,'lecturer_id'); }
 public function exams(){ return $this->hasMany(Exam::class,'lecturer_id'); }
 public function classrooms(){ return $this->belongsToMany(Classroom::class,'class_student','student_id','classroom_id')->withTimestamps(); }
 public function attempts(){ return $this->hasMany(ExamAttempt::class,'student_id'); }
 public function creatorLecturer(){ return $this->belongsTo(User::class,'created_by_lecturer_id'); }
 public function createdStudents(){ return $this->hasMany(User::class,'created_by_lecturer_id'); }
}
