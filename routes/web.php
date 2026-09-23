<?php

use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BranchSelectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExamController as AdminExamController;
use App\Http\Controllers\Admin\GuardianController as AdminGuardianController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\PassageGroupController as AdminPassageGroupController;
use App\Http\Controllers\Admin\PasswordController as AdminPasswordController;
use App\Http\Controllers\Admin\QuestionCategoryController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\ResultController as AdminResultController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Auth\GuardianPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Branch\ExamController as BranchExamController;
use App\Http\Controllers\Branch\GuardianController as BranchGuardianController;
use App\Http\Controllers\Branch\PassageGroupController as BranchPassageGroupController;
use App\Http\Controllers\Branch\PasswordController as BranchPasswordController;
use App\Http\Controllers\Branch\QuestionCategoryController as BranchQuestionCategoryController;
use App\Http\Controllers\Branch\QuestionController as BranchQuestionController;
use App\Http\Controllers\Branch\ResultController as BranchResultController;
use App\Http\Controllers\Branch\SchoolClassController as BranchSchoolClassController;
use App\Http\Controllers\Branch\StudentController as BranchStudentController;
use App\Http\Controllers\Branch\TeacherController as BranchTeacherController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\Guardian\PasswordController as GuardianPasswordUpdateController;
use App\Http\Controllers\Guardian\ProfileController as GuardianProfileController;
use App\Http\Controllers\Guardian\StudentController as GuardianStudentController;
use App\Http\Controllers\ResultPdfController;
use App\Http\Controllers\Student\ExamApiController as StudentExamApiController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\ExamController as TeacherExamController;
use App\Http\Controllers\Teacher\PassageGroupController as TeacherPassageGroupController;
use App\Http\Controllers\Teacher\PasswordController as TeacherPasswordUpdateController;
use App\Http\Controllers\Teacher\ProfileController as TeacherProfileController;
use App\Http\Controllers\Teacher\QuestionCategoryController as TeacherQuestionCategoryController;
use App\Http\Controllers\Teacher\QuestionController as TeacherQuestionController;
use App\Http\Controllers\Teacher\ResultController as TeacherResultController;
use App\Support\RoleRedirector;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = RoleRedirector::currentUser();

    return redirect($user ? RoleRedirector::dashboardUrl($user) : route('login'));
});

// Checked against the web guard (Super Admin/Branch), the student guard, the
// guardian guard, and the teacher guard, so an already-logged-in user of any
// kind is bounced to their own dashboard instead of seeing the login/
// password-reset forms again.
Route::middleware('guest:web,student,guardian,teacher')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp'])->name('password.email');
    Route::get('/verify-reset-code', [PasswordResetController::class, 'otp'])->name('password.otp');
    Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyOtp'])->name('password.otp.verify');
    Route::get('/reset-password', [PasswordResetController::class, 'resetForm'])->name('password.reset.form');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
    Route::post('/guardian-login/check-email', [GuardianPasswordController::class, 'checkEmail'])->name('guardian-login.check-email')->middleware('throttle:20,1');
    Route::post('/guardian-login/send-otp', [GuardianPasswordController::class, 'sendOtp'])->name('guardian-login.send-otp')->middleware('throttle:5,1');
    Route::post('/guardian-login/verify-otp', [GuardianPasswordController::class, 'verifyOtp'])->name('guardian-login.verify-otp')->middleware('throttle:10,1');
    Route::post('/guardian-login/create-password', [GuardianPasswordController::class, 'createPassword'])->name('guardian-login.create-password')->middleware('throttle:10,1');
});

Route::get('/verify-login-otp', [LoginOtpController::class, 'show'])->name('login.otp');
Route::post('/verify-login-otp', [LoginOtpController::class, 'verify'])->name('login.otp.verify')->middleware('throttle:10,1');
Route::post('/verify-login-otp/resend', [LoginOtpController::class, 'resend'])->name('login.otp.resend')->middleware('throttle:5,1');

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:web,student,guardian,teacher')->name('logout');

Route::get('/result-pdfs/{attempt}/{token}/result.pdf', ResultPdfController::class)->name('result-pdfs.show');
Route::get('/result-pdfs/{attempt}/{token}', ResultPdfController::class);

Route::middleware(['auth', 'active', 'role:Super Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::put('/password', [AdminPasswordController::class, 'update'])->name('password.update');
    Route::put('/account/email', [AdminAccountController::class, 'updateEmail'])->name('account.email.update');
    Route::get('/branch-selection', [BranchSelectionController::class, 'index'])->name('branch-selection.index');
    Route::post('/branch-selection', [BranchSelectionController::class, 'store'])->name('branch-selection.store');
    Route::delete('/branch-selection', [BranchSelectionController::class, 'clear'])->name('branch-selection.clear');
    Route::resource('branches', BranchController::class);
    Route::post('/branches/{branch}/toggle-active', [BranchController::class, 'toggleActive'])->name('branches.toggle-active');
    Route::put('/branches/{branch}/password', [BranchController::class, 'updatePassword'])->name('branches.password.update');
    Route::resource('classes', SchoolClassController::class)->parameters(['classes' => 'class']);
    Route::resource('subjects', SubjectController::class);
    Route::get('/guardians/search', [AdminGuardianController::class, 'search'])->name('guardians.search');
    Route::resource('question-categories', QuestionCategoryController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::middleware('branch_selected')->group(function () {
        Route::get('/students/create', [AdminStudentController::class, 'create'])->name('students.create');
        Route::post('/students', [AdminStudentController::class, 'store'])->name('students.store');
        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [AdminStudentController::class, 'show'])->name('students.show');
        Route::post('/students/{student}/toggle-active', [AdminStudentController::class, 'toggleActive'])->name('students.toggle-active');
        // Exams/Questions/Results are global resources (an Admin-created
        // Exam has branch_id = null and is visible to every Branch) — only
        // their index/browsing pages require a branch to be selected first;
        // the rest of their CRUD works the same with or without one.
        Route::get('/exams', [AdminExamController::class, 'index'])->name('exams.index');
        Route::get('/questions', [AdminQuestionController::class, 'index'])->name('questions.index');
        Route::get('/results', [AdminResultController::class, 'index'])->name('results.index');
    });
    Route::post('/exams', [AdminExamController::class, 'store'])->name('exams.store');
    Route::resource('exams', AdminExamController::class)->except(['store', 'index']);
    Route::post('/exams/{exam}/publish', [AdminExamController::class, 'publish'])->name('exams.publish');
    Route::post('/exams/{exam}/unpublish', [AdminExamController::class, 'unpublish'])->name('exams.unpublish');
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
    Route::get('/results/{attempt}', [AdminResultController::class, 'show'])->name('results.show');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
});

Route::middleware(['auth', 'active', 'role:Branch', 'single_session'])->prefix('branch')->name('branch.')->group(function () {
    Route::get('/dashboard', BranchDashboardController::class)->name('dashboard');
    Route::get('/password', [BranchPasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [BranchPasswordController::class, 'update'])->name('password.update');
    Route::resource('classes', BranchSchoolClassController::class)->parameters(['classes' => 'class']);
    Route::get('/guardians/search', [BranchGuardianController::class, 'search'])->name('guardians.search');
    Route::get('/students/create', [BranchStudentController::class, 'create'])->name('students.create');
    Route::post('/students', [BranchStudentController::class, 'store'])->name('students.store');
    Route::get('/students', [BranchStudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [BranchStudentController::class, 'show'])->name('students.show');
    Route::post('/students/{student}/toggle-active', [BranchStudentController::class, 'toggleActive'])->name('students.toggle-active');
    Route::get('/teachers', [BranchTeacherController::class, 'index'])->name('teachers.index');
    Route::post('/teachers', [BranchTeacherController::class, 'store'])->name('teachers.store');
    Route::put('/teachers/{teacher}', [BranchTeacherController::class, 'update'])->name('teachers.update');
    Route::delete('/teachers/{teacher}', [BranchTeacherController::class, 'destroy'])->name('teachers.destroy');
    Route::put('/teachers/{teacher}/password', [BranchTeacherController::class, 'updatePassword'])->name('teachers.password.update');
    Route::resource('question-categories', BranchQuestionCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/exams', [BranchExamController::class, 'store'])->name('exams.store');
    Route::resource('exams', BranchExamController::class)->except(['store']);
    Route::post('/exams/{exam}/publish', [BranchExamController::class, 'publish'])->name('exams.publish');
    Route::post('/exams/{exam}/unpublish', [BranchExamController::class, 'unpublish'])->name('exams.unpublish');
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
    Route::post('/results/{attempt}/send', [BranchResultController::class, 'send'])->name('results.send');
});

Route::middleware(['auth:student', 'active', 'single_session'])->prefix('student')->name('student.')->group(function () {
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

Route::middleware(['auth:guardian', 'single_session'])->prefix('guardian')->name('guardian.')->group(function () {
    Route::get('/dashboard', GuardianDashboardController::class)->name('dashboard');
    Route::get('/profile', [GuardianProfileController::class, 'show'])->name('profile');
    Route::get('/password', [GuardianPasswordUpdateController::class, 'edit'])->name('password.edit');
    Route::put('/password', [GuardianPasswordUpdateController::class, 'update'])->name('password.update');
    Route::get('/students/{student}', [GuardianStudentController::class, 'show'])->name('students.show');
    Route::get('/students/{student}/results/{attempt}', [GuardianStudentController::class, 'result'])->name('students.results.show');
    Route::get('/students/{student}/results/{attempt}/details', [GuardianStudentController::class, 'resultDetails'])->name('students.results.details');
});

Route::middleware(['auth:teacher', 'single_session'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', TeacherDashboardController::class)->name('dashboard');
    Route::get('/profile', [TeacherProfileController::class, 'show'])->name('profile');
    Route::get('/password', [TeacherPasswordUpdateController::class, 'edit'])->name('password.edit');
    Route::put('/password', [TeacherPasswordUpdateController::class, 'update'])->name('password.update');
    Route::resource('question-categories', TeacherQuestionCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/exams', [TeacherExamController::class, 'store'])->name('exams.store');
    Route::resource('exams', TeacherExamController::class)->except(['store']);
    Route::post('/exams/{exam}/publish', [TeacherExamController::class, 'publish'])->name('exams.publish');
    Route::post('/exams/{exam}/unpublish', [TeacherExamController::class, 'unpublish'])->name('exams.unpublish');
    Route::put('/exams/{exam}/category', [TeacherExamController::class, 'updateCategory'])->name('exams.category.update');
    Route::get('/questions', [TeacherQuestionController::class, 'index'])->name('questions.index');
    Route::get('/exams/{exam}/questions/create', [TeacherQuestionController::class, 'create'])->name('questions.create');
    Route::post('/exams/{exam}/questions', [TeacherQuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/{question}/edit', [TeacherQuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [TeacherQuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [TeacherQuestionController::class, 'destroy'])->name('questions.destroy');
    Route::get('/exams/{exam}/passage-groups/create', [TeacherPassageGroupController::class, 'create'])->name('passage-groups.create');
    Route::post('/exams/{exam}/passage-groups', [TeacherPassageGroupController::class, 'store'])->name('passage-groups.store');
    Route::get('/passage-groups/{passageGroup}/edit', [TeacherPassageGroupController::class, 'edit'])->name('passage-groups.edit');
    Route::put('/passage-groups/{passageGroup}', [TeacherPassageGroupController::class, 'update'])->name('passage-groups.update');
    Route::delete('/passage-groups/{passageGroup}', [TeacherPassageGroupController::class, 'destroy'])->name('passage-groups.destroy');
    Route::get('/exams/{exam}/passage-groups/{passageGroup}/questions/create', [TeacherQuestionController::class, 'createForPassage'])->name('passage-groups.questions.create');
    Route::post('/exams/{exam}/passage-groups/{passageGroup}/questions', [TeacherQuestionController::class, 'storeForPassage'])->name('passage-groups.questions.store');
    Route::post('/exams/{exam}/reorder', [TeacherExamController::class, 'reorderItems'])->name('exams.reorder');
    Route::get('/results', [TeacherResultController::class, 'index'])->name('results.index');
    Route::get('/results/{attempt}', [TeacherResultController::class, 'show'])->name('results.show');
    Route::post('/results/{attempt}/remark', [TeacherResultController::class, 'storeRemark'])->name('results.remark.store');
});
