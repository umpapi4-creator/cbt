<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Lecturer\{DashboardController as LecturerDashboard,CourseController,ClassroomController,QuestionController,ExamController,ProfileController as LecturerProfileController,MonitoringController};
use App\Http\Controllers\Student\{DashboardController as StudentDashboard,ExamController as StudentExamController,ProfileController as StudentProfileController,HistoryController as StudentHistoryController};
use App\Http\Controllers\Admin\{DashboardController as AdminDashboard,UserRoleController,StudentController as AdminStudentController,LecturerController as AdminLecturerController};

Route::get('/', fn()=>redirect('/home'));

Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class,'showLogin'])->name('login');
    Route::post('/login',[AuthController::class,'login'])->name('login.submit');
    Route::get('/register',[AuthController::class,'showRegister'])->name('register');
    Route::post('/register',[AuthController::class,'register'])->name('register.submit');
});

Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::get('/home',[AuthController::class,'home'])->middleware('auth')->name('home');


Route::prefix('admin')->name('admin.')->middleware(['auth','role:admin'])->group(function(){
    Route::get('/dashboard',AdminDashboard::class)->name('dashboard');
    Route::put('/users/{user}/role',[UserRoleController::class,'update'])->name('users.role.update');
    Route::delete('/users/{user}',[UserRoleController::class,'destroy'])->name('users.destroy');
    Route::get('/students/{user}/edit',[AdminStudentController::class,'edit'])->name('students.edit');
    Route::put('/students/{user}',[AdminStudentController::class,'update'])->name('students.update');
    Route::get('/lecturers/{user}/edit',[AdminLecturerController::class,'edit'])->name('lecturers.edit');
    Route::put('/lecturers/{user}',[AdminLecturerController::class,'update'])->name('lecturers.update');
});

Route::prefix('lecturer')->name('lecturer.')->middleware(['auth','role:lecturer'])->group(function(){
    Route::get('/dashboard',LecturerDashboard::class)->name('dashboard');
    Route::get('/profile',[LecturerProfileController::class,'edit'])->name('profile.edit');
    Route::put('/profile',[LecturerProfileController::class,'update'])->name('profile.update');

    Route::get('/courses',[CourseController::class,'index'])->name('courses.index');
    Route::post('/courses',[CourseController::class,'store'])->name('courses.store');
    Route::delete('/courses/{course}',[CourseController::class,'destroy'])->name('courses.destroy');

    Route::get('/classes',[ClassroomController::class,'index'])->name('classes.index');
    Route::post('/classes',[ClassroomController::class,'store'])->name('classes.store');
    Route::get('/classes/{classroom}',[ClassroomController::class,'show'])->name('classes.show');
    Route::put('/classes/{classroom}',[ClassroomController::class,'update'])->name('classes.update');
    Route::delete('/classes/{classroom}',[ClassroomController::class,'destroy'])->name('classes.destroy');
    Route::post('/classes/{classroom}/students',[ClassroomController::class,'storeStudent'])->name('classes.students.store');
    Route::post('/classes/{classroom}/students/attach',[ClassroomController::class,'attachStudent'])->name('classes.students.attach');
    Route::get('/classes/{classroom}/students/{student}/edit',[ClassroomController::class,'editStudent'])->name('classes.students.edit');
    Route::put('/classes/{classroom}/students/{student}',[ClassroomController::class,'updateStudent'])->name('classes.students.update');
    Route::delete('/classes/{classroom}/students/{student}',[ClassroomController::class,'detachStudent'])->name('classes.students.detach');
    Route::delete('/classes/{classroom}/students/{student}/account',[ClassroomController::class,'destroyStudentAccount'])->name('classes.students.account.destroy');

    Route::resource('exams',ExamController::class)->except('show');
    Route::get('/exams/{exam}/questions',[ExamController::class,'questions'])->name('exams.questions');
    Route::get('/exams/{exam}/questions/create',[QuestionController::class,'create'])->name('exams.questions.create');
    Route::post('/exams/{exam}/questions',[QuestionController::class,'store'])->name('exams.questions.store');
    Route::get('/exams/{exam}/questions/import',[QuestionController::class,'importForm'])->name('exams.questions.import');
    Route::post('/exams/{exam}/questions/import',[QuestionController::class,'importStore'])->name('exams.questions.import.store');
    Route::get('/exams/{exam}/questions/{question}/edit',[QuestionController::class,'edit'])->name('exams.questions.edit');
    Route::put('/exams/{exam}/questions/{question}',[QuestionController::class,'update'])->name('exams.questions.update');
    Route::delete('/exams/{exam}/questions/{question}',[QuestionController::class,'destroy'])->name('exams.questions.destroy');
    Route::get('/exams/{exam}/results',[ExamController::class,'results'])->name('exams.results');
    Route::post('/exams/{exam}/students/{student}/retake-permission',[ExamController::class,'toggleRetake'])->name('exams.retake-permission');
    Route::get('/monitoring/tab-notifications',[MonitoringController::class,'notifications'])->name('monitoring.tab-notifications');
});

Route::prefix('student')->name('student.')->middleware(['auth','role:student'])->group(function(){
    Route::get('/dashboard',StudentDashboard::class)->name('dashboard');
    Route::get('/profile',[StudentProfileController::class,'edit'])->name('profile.edit');
    Route::get('/history',StudentHistoryController::class)->name('history');
    Route::put('/profile',[StudentProfileController::class,'update'])->name('profile.update');
    Route::post('/exams/lookup',[StudentExamController::class,'lookup'])->name('exams.lookup');
    Route::post('/exams/{exam}/join',[StudentExamController::class,'join'])->name('exams.join');
    Route::post('/exams/{exam}/retake',[StudentExamController::class,'retake'])->name('exams.retake');
    Route::get('/attempts/{attempt}',[StudentExamController::class,'show'])->name('attempts.show');
    Route::post('/attempts/{attempt}/answer',[StudentExamController::class,'answer'])->name('attempts.answer');
    Route::post('/attempts/{attempt}/tab-violation',[StudentExamController::class,'tabViolation'])->name('attempts.tab-violation');
    Route::post('/attempts/{attempt}/submit',[StudentExamController::class,'submit'])->name('attempts.submit');
    Route::get('/attempts/{attempt}/result',[StudentExamController::class,'result'])->name('attempts.result');
});
