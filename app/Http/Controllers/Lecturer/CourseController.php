<?php
namespace App\Http\Controllers\Lecturer;
use App\Http\Controllers\Controller; use App\Models\Course; use Illuminate\Http\Request;
class CourseController extends Controller {
 public function index(){return view('lecturer.courses.index',['courses'=>Course::where('lecturer_id',auth()->id())->latest()->get()]);}
 public function store(Request $r){$d=$r->validate(['code'=>'required|max:30','name'=>'required|max:150','semester'=>'required|integer|min:1|max:14']);$d['lecturer_id']=auth()->id();Course::create($d);return back()->with('success','Mata kuliah ditambahkan.');}
 public function destroy(Course $course){abort_unless((int) $course->lecturer_id === (int) auth()->id(), 403);$course->delete();return back()->with('success','Mata kuliah dihapus.');}
}
