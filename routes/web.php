<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExamController as AdminExamController;
use App\Http\Controllers\Admin\PassageGroupController as AdminPassageGroupController;
use App\Http\Controllers\Admin\PasswordController as AdminPasswordController;
use App\Http\Controllers\Admin\QuestionCategoryController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\ResultController as AdminResultController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\StudentPasswordController;
use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Branch\ExamController as BranchExamController;
use App\Http\Controllers\Branch\PassageGroupController as BranchPassageGroupController;
use App\Http\Controllers\Branch\PasswordController as BranchPasswordController;
use App\Http\Controllers\Branch\QuestionCategoryController as BranchQuestionCategoryController;
use App\Http\Controllers\Branch\QuestionController as BranchQuestionController;
use App\Http\Controllers\Branch\ResultController as BranchResultController;
use App\Http\Controllers\Branch\SchoolClassController as BranchSchoolClassController;
use App\Http\Controllers\Branch\StudentController as BranchStudentController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ExamApiController as StudentExamApiController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp'])->name('password.email');
    Route::get('/verify-reset-code', [PasswordResetController::class, 'otp'])->name('password.otp');
    Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyOtp'])->name('password.otp.verify');
    Route::get('/reset-password', [PasswordResetController::class, 'resetForm'])->name('password.reset.form');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
    Route::post('/student-login/check-email', [StudentPasswordController::class, 'checkEmail'])->name('student-login.check-email')->middleware('throttle:20,1');
    Route::post('/student-login/send-otp', [StudentPasswordController::class, 'sendOtp'])->name('student-login.send-otp')->middleware('throttle:5,1');
    Route::post('/student-login/verify-otp', [StudentPasswordController::class, 'verifyOtp'])->name('student-login.verify-otp')->middleware('throttle:10,1');
    Route::post('/student-login/create-password', [StudentPasswordController::class, 'createPassword'])->name('student-login.create-password')->middleware('throttle:10,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:web,student')->name('logout');

Route::middleware(['auth', 'active', 'role:Super Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::put('/password', [AdminPasswordController::class, 'update'])->name('password.update');
    Route::resource('branches', BranchController::class);
    Route::post('/branches/{branch}/toggle-active', [BranchController::class, 'toggleActive'])->name('branches.toggle-active');
    Route::put('/branches/{branch}/password', [BranchController::class, 'updatePassword'])->name('branches.password.update');
    Route::resource('classes', SchoolClassController::class)->parameters(['classes' => 'class']);
    Route::resource('students', AdminStudentController::class);
    Route::post('/students/{student}/toggle-active', [AdminStudentController::class, 'toggleActive'])->name('students.toggle-active');
    Route::put('/students/{student}/password', [AdminStudentController::class, 'updatePassword'])->name('students.password.update');
    Route::resource('question-categories', QuestionCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('exams', AdminExamController::class);
    Route::post('/exams/{exam}/publish', [AdminExamController::class, 'publish'])->name('exams.publish');
    Route::post('/exams/{exam}/unpublish', [AdminExamController::class, 'unpublish'])->name('exams.unpublish');
    Route::put('/exams/{exam}/settings', [AdminExamController::class, 'updateSettings'])->name('exams.settings.update');
    Route::put('/exams/{exam}/category', [AdminExamController::class, 'updateCategory'])->name('exams.category.update');
    Route::get('/exams/{exam}/questions/create', [AdminQuestionController::class, 'create'])->name('questions.create');
    Route::post('/exams/{exam}/questions', [AdminQuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/{question}/edit', [AdminQuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [AdminQuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [AdminQuestionController::class, 'destroy'])->name('questions.destroy');
    Route::get('/exams/{exam}/passage-groups/create', [AdminPassageGroupController::class, 'create'])->name('passage-groups.create');
    Route::post('/exams/{exam}/passage-groups', [AdminPassageGroupController::class, 'store'])->name('passage-groups.store');
    Route::get('/passage-groups/{passageGroup}/edit', [AdminPassageGroupController::class, 'edit'])->name('passage-groups.edit');
    Route::put('/passage-groups/{passageGroup}', [AdminPassageGroupController::class, 'update'])->name('passage-groups.update');
    Route::delete('/passage-groups/{passageGroup}', [AdminPassageGroupController::class, 'destroy'])->name('passage-groups.destroy');
    Route::get('/exams/{exam}/passage-groups/{passageGroup}/questions/create', [AdminQuestionController::class, 'createForPassage'])->name('passage-groups.questions.create');
    Route::post('/exams/{exam}/passage-groups/{passageGroup}/questions', [AdminQuestionController::class, 'storeForPassage'])->name('passage-groups.questions.store');
    Route::post('/exams/{exam}/reorder', [AdminExamController::class, 'reorderItems'])->name('exams.reorder');
    Route::get('/results', [AdminResultController::class, 'index'])->name('results.index');
    Route::get('/results/{attempt}', [AdminResultController::class, 'show'])->name('results.show');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth', 'active', 'role:Branch'])->prefix('branch')->name('branch.')->group(function () {
    Route::get('/dashboard', BranchDashboardController::class)->name('dashboard');
    Route::get('/password', [BranchPasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [BranchPasswordController::class, 'update'])->name('password.update');
    Route::resource('classes', BranchSchoolClassController::class)->parameters(['classes' => 'class']);
    Route::resource('students', BranchStudentController::class);
    Route::post('/students/{student}/toggle-active', [BranchStudentController::class, 'toggleActive'])->name('students.toggle-active');
    Route::resource('question-categories', BranchQuestionCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('exams', BranchExamController::class);
    Route::post('/exams/{exam}/publish', [BranchExamController::class, 'publish'])->name('exams.publish');
    Route::post('/exams/{exam}/unpublish', [BranchExamController::class, 'unpublish'])->name('exams.unpublish');
    Route::put('/exams/{exam}/settings', [BranchExamController::class, 'updateSettings'])->name('exams.settings.update');
    Route::put('/exams/{exam}/category', [BranchExamController::class, 'updateCategory'])->name('exams.category.update');
    Route::get('/questions', [BranchQuestionController::class, 'index'])->name('questions.index');
    Route::get('/exams/{exam}/questions/create', [BranchQuestionController::class, 'create'])->name('questions.create');
    Route::post('/exams/{exam}/questions', [BranchQuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/{question}/edit', [BranchQuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [BranchQuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [BranchQuestionController::class, 'destroy'])->name('questions.destroy');
    Route::get('/exams/{exam}/passage-groups/create', [BranchPassageGroupController::class, 'create'])->name('passage-groups.create');
    Route::post('/exams/{exam}/passage-groups', [BranchPassageGroupController::class, 'store'])->name('passage-groups.store');
    Route::get('/passage-groups/{passageGroup}/edit', [BranchPassageGroupController::class, 'edit'])->name('passage-groups.edit');
    Route::put('/passage-groups/{passageGroup}', [BranchPassageGroupController::class, 'update'])->name('passage-groups.update');
    Route::delete('/passage-groups/{passageGroup}', [BranchPassageGroupController::class, 'destroy'])->name('passage-groups.destroy');
    Route::get('/exams/{exam}/passage-groups/{passageGroup}/questions/create', [BranchQuestionController::class, 'createForPassage'])->name('passage-groups.questions.create');
    Route::post('/exams/{exam}/passage-groups/{passageGroup}/questions', [BranchQuestionController::class, 'storeForPassage'])->name('passage-groups.questions.store');
    Route::post('/exams/{exam}/reorder', [BranchExamController::class, 'reorderItems'])->name('exams.reorder');
    Route::get('/results', [BranchResultController::class, 'index'])->name('results.index');
    Route::get('/results/{attempt}', [BranchResultController::class, 'show'])->name('results.show');
});

Route::middleware(['auth:student', 'active'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentExamController::class, 'dashboard'])->name('dashboard');
    Route::get('/exams/available', [StudentExamController::class, 'available'])->name('exams.available');
    Route::get('/exams/upcoming', [StudentExamController::class, 'upcoming'])->name('exams.upcoming');
    Route::get('/exams/mine', [StudentExamController::class, 'mine'])->name('exams.mine');
    Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
    Route::post('/exams/{exam}/start', [StudentExamController::class, 'start'])->name('exams.start');
    Route::get('/attempts/{attempt}', [StudentExamController::class, 'attempt'])->name('attempts.show');
    Route::get('/attempts/{attempt}/state', [StudentExamApiController::class, 'state'])->name('attempts.state');
    Route::post('/attempts/{attempt}/answer', [StudentExamApiController::class, 'answer'])->name('attempts.answer');
    Route::post('/attempts/{attempt}/submit', [StudentExamApiController::class, 'submit'])->name('attempts.submit');
    Route::get('/profile', [StudentExamController::class, 'profile'])->name('profile');
    Route::get('/results', [StudentExamController::class, 'results'])->name('results.index');
    Route::get('/results/{attempt}', [StudentExamController::class, 'result'])->name('results.show');
});
