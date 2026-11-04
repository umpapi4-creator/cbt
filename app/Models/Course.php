<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Course extends Model { protected $fillable=['lecturer_id','code','name','semester']; public function lecturer(){return $this->belongsTo(User::class,'lecturer_id');} public function questions(){return $this->hasMany(Question::class);} public function exams(){return $this->hasMany(Exam::class);} }
