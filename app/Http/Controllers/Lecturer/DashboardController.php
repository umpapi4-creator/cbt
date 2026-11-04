<?php
namespace App\Http\Controllers\Lecturer;
use App\Http\Controllers\Controller;
use App\Models\{Exam,Question,ExamAttempt};
class DashboardController extends Controller {
 public function __invoke(){ $uid=auth()->id(); $exams=Exam::where('lecturer_id',$uid)->withCount('attempts')->latest()->take(6)->get(); return view('lecturer.dashboard',['questionCount'=>Question::where('lecturer_id',$uid)->whereNotNull('exam_id')->count(),'examCount'=>Exam::where('lecturer_id',$uid)->count(),'activeCount'=>Exam::where('lecturer_id',$uid)->where('status','published')->count(),'attemptCount'=>ExamAttempt::whereHas('exam',fn($q)=>$q->where('lecturer_id',$uid))->count(),'exams'=>$exams]); }
}
