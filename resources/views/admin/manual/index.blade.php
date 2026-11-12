@extends('layouts.admin')

@section('title', 'User Manual')
@section('page-title', 'User Manual')

@php
    $siteName = \App\Models\Setting::current()->site_name ?: config('app.name');

    $roles = [
        ['id' => 'super-admin', 'icon' => 'bi-shield-lock-fill', 'color' => '#4f46e5', 'name' => 'Super Admin', 'who' => 'Head office / system owner', 'login' => 'Email + Password + OTP'],
        ['id' => 'branch', 'icon' => 'bi-building-fill', 'color' => '#0891b2', 'name' => 'Branch', 'who' => 'Each tuition centre / location', 'login' => 'User ID + Password + OTP'],
        ['id' => 'teacher', 'icon' => 'bi-person-workspace', 'color' => '#059669', 'name' => 'Teacher', 'who' => 'Teachers working under a branch', 'login' => 'Email + Password + OTP'],
        ['id' => 'student', 'icon' => 'bi-mortarboard-fill', 'color' => '#d97706', 'name' => 'Student', 'who' => 'Learners who take the exams', 'login' => 'NRICH Student ID + OTP (or Teacher Override)'],
        ['id' => 'guardian', 'icon' => 'bi-people-fill', 'color' => '#db2777', 'name' => 'Guardian', 'who' => 'Parents following their children', 'login' => 'Login tab not enabled yet'],
    ];
@endphp

@section('content')
    <style>
        .manual-hero { display:flex; gap:1.25rem; align-items:center; padding:1.5rem; border-radius:1rem; background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; margin-bottom:1.5rem; }
        .manual-hero i.hero-icon { font-size:2.5rem; opacity:.9; }
        .manual-hero h2 { margin:0 0 .25rem; font-size:1.5rem; font-weight:700; color:#fff; }
        .manual-hero p { margin:0; opacity:.9; }
        .manual-layout { display:grid; grid-template-columns:240px minmax(0,1fr); gap:1.5rem; align-items:start; }
        .manual-toc { position:sticky; top:1rem; background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; padding:1rem; }
        .manual-toc h6 { font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:.5rem; }
        .manual-toc a { display:flex; gap:.5rem; align-items:center; padding:.4rem .5rem; border-radius:.5rem; color:#374151; text-decoration:none; font-size:.9rem; }
        .manual-toc a:hover { background:#f3f4f6; color:#4f46e5; }
        .manual-section { scroll-margin-top:1rem; }
        .manual-section h2 { display:flex; align-items:center; gap:.6rem; }
        .role-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:1rem; }
        .role-card { border:1px solid #e5e7eb; border-radius:.75rem; padding:1rem; background:#fff; text-decoration:none; color:inherit; transition:transform .15s, box-shadow .15s; display:block; }
        .role-card:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,0,0,.06); color:inherit; }
        .role-card .role-icon { width:42px; height:42px; border-radius:.6rem; display:grid; place-items:center; color:#fff; font-size:1.25rem; margin-bottom:.6rem; }
        .role-card strong { display:block; font-size:1rem; }
        .role-card small { color:#6b7280; display:block; }
        .role-card .role-login { margin-top:.5rem; font-size:.8rem; background:#f3f4f6; border-radius:.4rem; padding:.25rem .5rem; display:inline-block; }
        .steps { counter-reset:step; list-style:none; padding:0; margin:0; }
        .steps li { counter-increment:step; position:relative; padding:0 0 .9rem 2.6rem; }
        .steps li::before { content:counter(step); position:absolute; left:0; top:0; width:1.8rem; height:1.8rem; border-radius:50%; background:#eef2ff; color:#4f46e5; font-weight:700; display:grid; place-items:center; font-size:.85rem; }
        .tip { border-left:4px solid #10b981; background:#ecfdf5; padding:.75rem 1rem; border-radius:.5rem; margin:.75rem 0; }
        .warn { border-left:4px solid #f59e0b; background:#fffbeb; padding:.75rem 1rem; border-radius:.5rem; margin:.75rem 0; }
        .menu-table td:first-child { white-space:nowrap; font-weight:600; }
        .manual-section .accordion-button { font-weight:600; }
        .faq dt { font-weight:600; margin-top:.75rem; }
        .faq dd { margin-left:0; color:#4b5563; }
        @media (max-width: 991px) { .manual-layout { grid-template-columns:1fr; } .manual-toc { position:static; } }
    </style>

    <div class="manual-hero">
        <i class="bi bi-book-half hero-icon"></i>
        <div>
            <h2>{{ $siteName }} — Complete User Manual</h2>
            <p>A simple, step-by-step guide for every person who uses the system: Super Admin, Branch, Teacher, Student and Guardian. Use the menu on the left to jump to any part.</p>
        </div>
    </div>

    <div class="manual-layout">
        <nav class="manual-toc" aria-label="Manual contents">
            <h6>Contents</h6>
            <a href="#overview"><i class="bi bi-compass"></i> How the system works</a>
            <a href="#login"><i class="bi bi-box-arrow-in-right"></i> Login methods</a>
            <a href="#super-admin"><i class="bi bi-shield-lock-fill"></i> Super Admin panel</a>
            <a href="#branch"><i class="bi bi-building-fill"></i> Branch panel</a>
            <a href="#teacher"><i class="bi bi-person-workspace"></i> Teacher panel</a>
            <a href="#student"><i class="bi bi-mortarboard-fill"></i> Student panel</a>
            <a href="#guardian"><i class="bi bi-people-fill"></i> Guardian panel</a>
            <a href="#exam-guide"><i class="bi bi-journal-plus"></i> Creating an exam</a>
            <a href="#results"><i class="bi bi-bar-chart-fill"></i> Results journey</a>
            <a href="#security"><i class="bi bi-lock-fill"></i> Security &amp; sessions</a>
            <a href="#faq"><i class="bi bi-question-circle-fill"></i> FAQ &amp; troubleshooting</a>
        </nav>

        <div>
            {{-- OVERVIEW --}}
            <section id="overview" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-compass text-primary"></i>How the system works</h2>
                        <p>Five types of users, each with their own panel. Click a card to read about it.</p>
                    </div>
                </div>

                <div class="role-grid mb-4">
                    @foreach ($roles as $role)
                        <a href="#{{ $role['id'] }}" class="role-card">
                            <div class="role-icon" style="background: {{ $role['color'] }}"><i class="bi {{ $role['icon'] }}"></i></div>
                            <strong>{{ $role['name'] }}</strong>
                            <small>{{ $role['who'] }}</small>
                            <span class="role-login"><i class="bi bi-key"></i> {{ $role['login'] }}</span>
                        </a>
                    @endforeach
                </div>

                <h5>The big picture in 6 steps</h5>
                <ol class="steps">
                    <li><strong>Super Admin</strong> sets up Branches, Grades, Subjects and Question Categories, and configures mail &amp; the Teacher Override password in Settings.</li>
                    <li><strong>Super Admin or a Branch</strong> creates an Exam, adds questions (or reading passages with questions) and publishes it.</li>
                    <li><strong>Students</strong> log in with their NRICH Student ID. Student details, grade and subjects come automatically from Zoho — nobody types them in.</li>
                    <li>Students see the exams meant for their grade and subject, take them inside the time limit, and submit. Marks are calculated automatically.</li>
                    <li><strong>Branch</strong> reviews the result, writes feedback and sends the result PDF. <strong>Teachers</strong> can add remarks, which are emailed to the Student and Guardian.</li>
                    <li><strong>Students and Guardians</strong> view the results; <strong>Super Admin</strong> watches everything from Results and Logs.</li>
                </ol>
            </section>

            {{-- LOGIN --}}
            <section id="login" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-box-arrow-in-right text-primary"></i>Login methods</h2>
                        <p>Everyone uses the same login page. First choose who you are (the tabs at the top), then follow the steps.</p>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr><th>User</th><th>What you enter</th><th>Second step</th><th>Forgot password?</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><i class="bi bi-shield-lock-fill"></i> Super Admin</td><td>Email + Password</td><td>6-digit OTP sent to email</td><td>Yes — “Forgot password” link</td></tr>
                            <tr><td><i class="bi bi-building-fill"></i> Branch</td><td>Branch User ID + Password</td><td>6-digit OTP sent to the branch email</td><td>Yes — “Forgot password” link</td></tr>
                            <tr><td><i class="bi bi-person-workspace"></i> Teacher</td><td>Teacher Email + Password</td><td>6-digit OTP sent to email</td><td>Yes — “Forgot password” link</td></tr>
                            <tr><td><i class="bi bi-mortarboard-fill"></i> Student</td><td>NRICH Student ID (e.g. NL1184)</td><td>OTP sent by Zoho to the registered email</td><td>Not needed — there is no student password</td></tr>
                            <tr><td><i class="bi bi-mortarboard-fill"></i> Student (Teacher Override)</td><td>NRICH Student ID + tick “Teacher Override” + common password</td><td>None — logs in directly</td><td>Super Admin sets it in Settings</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="warn"><i class="bi bi-exclamation-triangle-fill"></i> <strong>Guardian login:</strong> the Guardian panel exists, but there is currently <strong>no Guardian tab</strong> on the login page, so guardians cannot sign in yet. Until it is switched on, guardians receive results by email (Teacher remarks are sent to the guardian’s email).</div>

                <div class="accordion" id="loginAccordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#login-staff">Super Admin, Branch &amp; Teacher login (Password + OTP)</button></h3>
                        <div id="login-staff" class="accordion-collapse collapse show" data-bs-parent="#loginAccordion">
                            <div class="accordion-body">
                                <ol class="steps">
                                    <li>Open the login page and click your tab: <strong>Super Admin</strong>, <strong>Branch</strong> or <strong>Teacher</strong>.</li>
                                    <li>Enter your email and password and press <strong>Login</strong>. Tick “Remember me” on your own device to stay signed in.</li>
                                    <li>Check your email for a <strong>6-digit code</strong> and type it on the verification screen.</li>
                                    <li>You are taken straight to your dashboard.</li>
                                </ol>
                                <div class="tip"><i class="bi bi-lightbulb-fill"></i> The code is valid for <strong>5 minutes</strong>. Didn’t get it? Check Spam, then press <strong>Resend</strong> (available after 60 seconds). After 5 wrong codes you must log in again.</div>
                                <div class="warn"><i class="bi bi-exclamation-triangle-fill"></i> Choose the correct tab. Branches sign in with their unique User ID (set by the Super Admin), not their email — several branches may share one email. A Branch User ID will not work on the Super Admin tab and vice-versa. If a Branch is deactivated, its Branch and Teacher logins stop working.</div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#login-student">Student login (NRICH ID + OTP)</button></h3>
                        <div id="login-student" class="accordion-collapse collapse" data-bs-parent="#loginAccordion">
                            <div class="accordion-body">
                                <ol class="steps">
                                    <li>Click the <strong>Student</strong> tab and type your <strong>NRICH Student ID</strong> (for example <code>NL1184</code>).</li>
                                    <li>Press <strong>Login</strong>. Zoho checks the ID and emails a code to the email registered with Zoho (usually the parent’s email).</li>
                                    <li>Enter the code on the next screen.</li>
                                    <li>Done! The first time, your account is created automatically from Zoho (name, branch, grade, subjects). Every later login refreshes these details.</li>
                                </ol>
                                <div class="warn"><i class="bi bi-exclamation-triangle-fill"></i> If you see “Enrolment Location does not match any Branch”, the Super Admin must add a Branch whose name matches the student’s Zoho location (for example “Ringwood”).</div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#login-override">Teacher Override (log a student in without OTP)</button></h3>
                        <div id="login-override" class="accordion-collapse collapse" data-bs-parent="#loginAccordion">
                            <div class="accordion-body">
                                <p>Used in the classroom when a student cannot receive the OTP email.</p>
                                <ol class="steps">
                                    <li>On the <strong>Student</strong> tab, type the student’s NRICH ID.</li>
                                    <li>Tick <strong>Teacher Override</strong> — a password box appears.</li>
                                    <li>Enter the common Teacher Override password and press <strong>Login</strong>. The student goes straight to their dashboard.</li>
                                </ol>
                                <div class="tip"><i class="bi bi-lightbulb-fill"></i> The Super Admin sets this one global password in <strong>Settings → Teacher Override Password</strong>. It works for every student in every branch, so share it only with teachers.</div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#login-forgot">Forgot password</button></h3>
                        <div id="login-forgot" class="accordion-collapse collapse" data-bs-parent="#loginAccordion">
                            <div class="accordion-body">
                                <ol class="steps">
                                    <li>On the login page choose your tab and click <strong>Forgot password?</strong></li>
                                    <li>Enter your account email; a 6-digit reset code is emailed to you.</li>
                                    <li>Enter the code, then choose and confirm a new password.</li>
                                    <li>Log in with the new password.</li>
                                </ol>
                                <div class="tip"><i class="bi bi-lightbulb-fill"></i> Super Admin can also reset any Branch password from <strong>Branches</strong>, and a Branch can reset its Teachers’ passwords from <strong>Teachers</strong>.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- SUPER ADMIN --}}
            <section id="super-admin" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-shield-lock-fill text-primary"></i>Super Admin panel</h2>
                        <p>The control centre for the whole system. Everything the left-hand menu offers:</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table menu-table align-middle">
                        <tbody>
                            <tr><td><i class="bi bi-grid-1x2-fill"></i> Dashboard</td><td>Quick numbers: branches, students, exams, attempts and recent activity.</td></tr>
                            <tr><td><i class="bi bi-diagram-3-fill"></i> Branches</td><td>Add, edit, activate/deactivate branches and reset a branch’s password. <strong>The branch name must match the student’s Zoho location</strong> so new students land in the right branch. Deactivating a branch blocks its Branch, Teacher and Student logins.</td></tr>
                            <tr><td><i class="bi bi-collection-fill"></i> Grades</td><td>Create grades (e.g. “Grade 2”). Students are matched to a grade from Zoho, and exams are shown to students of the selected grades.</td></tr>
                            <tr><td><i class="bi bi-book-fill"></i> Subjects</td><td>Create subjects (e.g. “English”, “Maths”). Names should match Zoho class subjects so students only see exams for subjects they study.</td></tr>
                            <tr><td><i class="bi bi-tags-fill"></i> Question Category</td><td>Labels to organise exams/questions (e.g. “Reading”, “Algebra”). Categories created here are global.</td></tr>
                            <tr><td><i class="bi bi-journal-check"></i> Exams</td><td>Create exams for all branches or one branch, add questions and passages, publish/unpublish. See <a href="#exam-guide">Creating an exam</a>.</td></tr>
                            <tr><td><i class="bi bi-bar-chart-fill"></i> Results</td><td>Every submitted attempt from every branch, with search and filters. Open one to see each answer.</td></tr>
                            <tr><td><i class="bi bi-clock-history"></i> Logs</td><td>Every login attempt (success or failure) for every user type, with reason, time and branch — the first place to look when someone says “I can’t log in”.</td></tr>
                            <tr><td><i class="bi bi-book-half"></i> User Manual</td><td>This page.</td></tr>
                            <tr><td><i class="bi bi-gear-fill"></i> Settings</td><td>System name &amp; logo, SMTP mail settings (needed for all OTP and result emails), Teacher Override password, your email and password.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="tip"><i class="bi bi-lightbulb-fill"></i> <strong>First-time setup checklist:</strong> Settings → mail (test it by logging out and in) → Teacher Override password → Branches → Grades → Subjects → Question Categories → first Exam.</div>
            </section>

            {{-- BRANCH --}}
            <section id="branch" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-building-fill" style="color:#0891b2"></i>Branch panel</h2>
                        <p>For each centre. A branch manages its own teachers and exams, and reviews results.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table menu-table align-middle">
                        <tbody>
                            <tr><td><i class="bi bi-grid-1x2-fill"></i> Dashboard</td><td>Overview of the branch: exams, attempts and recent results.</td></tr>
                            <tr><td><i class="bi bi-person-workspace"></i> Teachers</td><td>Add teachers (name + email — login details are emailed to them), edit, reset password or remove.</td></tr>
                            <tr><td><i class="bi bi-journal-check"></i> Exams</td><td>Create and manage the branch’s own exams, questions, passages and categories; publish when ready.</td></tr>
                            <tr><td><i class="bi bi-bar-chart-fill"></i> Results</td><td>See submitted attempts, open a result, write review/feedback and press <strong>Review &amp; Send Result</strong> to generate the result PDF and forward it to Zoho.</td></tr>
                            <tr><td><i class="bi bi-key-fill"></i> Change Password</td><td>Change the branch login password.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="warn"><i class="bi bi-exclamation-triangle-fill"></i> A Branch account can be signed in on <strong>one device at a time</strong>. Logging in somewhere else signs out the previous device.</div>
            </section>

            {{-- TEACHER --}}
            <section id="teacher" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-person-workspace" style="color:#059669"></i>Teacher panel</h2>
                        <p>Teachers are created by their Branch. They review results and add personal remarks.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table menu-table align-middle">
                        <tbody>
                            <tr><td><i class="bi bi-grid-1x2-fill"></i> Dashboard</td><td>Summary of recent results to look at.</td></tr>
                            <tr><td><i class="bi bi-bar-chart-fill"></i> Results</td><td>Open a student’s result, write a <strong>remark</strong> and save. The result with the remark is emailed to the Student’s email and, if different, to the Guardian’s email.</td></tr>
                            <tr><td><i class="bi bi-person-circle"></i> Profile</td><td>Your details and branch.</td></tr>
                            <tr><td><i class="bi bi-shield-lock-fill"></i> Change Password</td><td>Set a new password (your first password comes by email when the Branch creates you).</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="tip"><i class="bi bi-lightbulb-fill"></i> Teachers also know the Teacher Override password and can use it on the Student tab to sign a student in during class.</div>
            </section>

            {{-- STUDENT --}}
            <section id="student" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-mortarboard-fill" style="color:#d97706"></i>Student panel</h2>
                        <p>Where students take exams and see their results.</p>
                    </div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table menu-table align-middle">
                        <tbody>
                            <tr><td><i class="bi bi-grid-1x2-fill"></i> Dashboard</td><td>Your exams at a glance and recent scores.</td></tr>
                            <tr><td><i class="bi bi-journal-check"></i> Available Exams</td><td>Exams open right now for your grade and subjects — press <strong>Start</strong>.</td></tr>
                            <tr><td><i class="bi bi-calendar-event"></i> Upcoming Exams</td><td>Published exams that open later, with start date and time.</td></tr>
                            <tr><td><i class="bi bi-bar-chart-fill"></i> Results</td><td>Your score, percentage and each question’s answer after submitting.</td></tr>
                            <tr><td><i class="bi bi-person-badge-fill"></i> Profile</td><td>Your details from Zoho: name, branch, grade, class and subjects.</td></tr>
                        </tbody>
                    </table>
                </div>
                <h5>Taking an exam</h5>
                <ol class="steps">
                    <li>Go to <strong>Available Exams</strong> and press <strong>Start</strong> on the exam.</li>
                    <li>A timer appears. Choose one answer per question — answers are <strong>saved automatically</strong> as you go.</li>
                    <li>Reading passages show the passage (and picture, if any) with its questions.</li>
                    <li>Press <strong>Submit</strong> when finished. If time runs out, the exam is submitted automatically.</li>
                    <li>See your marks in <strong>Results</strong>.</li>
                </ol>
                <div class="tip"><i class="bi bi-lightbulb-fill"></i> Lost internet or closed the browser? Log in again and open the same exam — your saved answers and the remaining time are still there (the timer keeps running).</div>
                <div class="warn"><i class="bi bi-exclamation-triangle-fill"></i> Don’t see an exam? It may not be published yet, may be for a different grade/subject, its time window may have ended, or you have used all allowed attempts.</div>
            </section>

            {{-- GUARDIAN --}}
            <section id="guardian" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-people-fill" style="color:#db2777"></i>Guardian panel</h2>
                        <p>For parents to follow the results of all their children in one place.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table menu-table align-middle">
                        <tbody>
                            <tr><td><i class="bi bi-people-fill"></i> My Students</td><td>All children linked to the guardian’s email. Switch between children and open any result with full answer details.</td></tr>
                            <tr><td><i class="bi bi-person-circle"></i> Profile</td><td>Guardian details.</td></tr>
                            <tr><td><i class="bi bi-shield-lock-fill"></i> Change Password</td><td>Set a new password.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="tip"><i class="bi bi-lightbulb-fill"></i> How it works once enabled: a first-time guardian verifies their email with an OTP and creates a password; after that it is Email + Password + OTP every time.</div>
                <div class="warn"><i class="bi bi-exclamation-triangle-fill"></i> The Guardian tab is not shown on the login page right now (see <a href="#login">Login methods</a>). Guardians still get result and remark emails.</div>
            </section>

            {{-- EXAM GUIDE --}}
            <section id="exam-guide" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-journal-plus text-primary"></i>Creating an exam (Super Admin &amp; Branch)</h2>
                        <p>Same steps in both panels. A Super Admin can make an exam for all branches or one branch; a Branch makes exams for itself.</p>
                    </div>
                </div>
                <ol class="steps">
                    <li>Open <strong>Exams</strong> and press <strong>Create Exam</strong>.</li>
                    <li>Fill in: <strong>Title</strong>, optional description, <strong>Grade(s)</strong> (one or more), <strong>Subject</strong>, <strong>Start</strong> and <strong>End</strong> date &amp; time, <strong>Duration</strong> (minutes, up to 24 hours) and <strong>Attempts allowed</strong> (1–20).</li>
                    <li>Optional switches: <em>Randomize questions</em>, <em>Randomize answers</em>, and <em>Negative marking</em> (marks taken off for each wrong answer).</li>
                    <li>Save. The exam starts as a <span class="badge bg-secondary">Draft</span> — students cannot see it yet.</li>
                    <li>Add questions: each is a multiple-choice question with its marks, at least 2 options and one correct option. You can add many questions at once. Maths symbols and formatting are supported.</li>
                    <li>For reading comprehension, add a <strong>Passage</strong> (text and optional image) and then add questions under it.</li>
                    <li>Drag to reorder questions/passages, and set a <strong>Category</strong> if you use them.</li>
                    <li>Press <strong>Publish</strong>. Status becomes <span class="badge bg-success">Published</span> and students of the chosen grade &amp; subject see it during the start–end window.</li>
                </ol>
                <div class="warn"><i class="bi bi-exclamation-triangle-fill"></i> Once any student has attempted an exam it <strong>cannot be deleted or unpublished</strong>, so results stay safe. Check everything before publishing.</div>
            </section>

            {{-- RESULTS --}}
            <section id="results" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-bar-chart-fill text-primary"></i>Results journey</h2>
                        <p>What happens to a result after the student presses Submit.</p>
                    </div>
                </div>
                <ol class="steps">
                    <li><strong>Automatic marking</strong> — correct, wrong and unanswered counts, marks and percentage are calculated instantly. The student sees the result right away.</li>
                    <li><strong>Branch review</strong> — the Branch opens the result in <em>Results</em>, writes feedback and presses <strong>Review &amp; Send Result</strong>. A professional PDF is created and its link is sent to Zoho.</li>
                    <li><strong>Teacher remark</strong> — a Teacher can add a remark; the result with the remark is emailed to the Student and Guardian.</li>
                    <li><strong>Viewing</strong> — Student (Results), Guardian (My Students), Branch, Teacher and Super Admin can all open the result.</li>
                </ol>
            </section>

            {{-- SECURITY --}}
            <section id="security" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-lock-fill text-primary"></i>Security &amp; sessions</h2>
                        <p>Good to know for every user.</p>
                    </div>
                </div>
                <ul>
                    <li><strong>Every login needs an OTP</strong> (except Teacher Override), so a stolen password alone is not enough.</li>
                    <li><strong>One device at a time</strong> for Student, Branch, Teacher and Guardian accounts — a new login signs the old device out.</li>
                    <li><strong>Remember me</strong> keeps you signed in for up to 7 days on that device. Don’t use it on shared computers.</li>
                    <li>Always press <strong>Logout</strong> (bottom of the left menu) on shared computers.</li>
                    <li>Deactivated branches or students cannot log in; reactivate them from the Super Admin panel.</li>
                    <li>All login attempts are recorded in <strong>Logs</strong>.</li>
                </ul>
            </section>

            {{-- FAQ --}}
            <section id="faq" class="content-panel manual-section">
                <div class="panel-header">
                    <div>
                        <h2><i class="bi bi-question-circle-fill text-primary"></i>FAQ &amp; troubleshooting</h2>
                        <p>Quick answers to the most common questions.</p>
                    </div>
                </div>
                <dl class="faq">
                    <dt>I didn’t receive the OTP email.</dt>
                    <dd>Check Spam/Junk, wait one minute and press Resend. Students’ codes go to the email registered in Zoho (often the parent’s). If nobody receives any emails, the Super Admin should check <em>Settings → SMTP / Mail Settings</em>.</dd>

                    <dt>“The code has expired” or “Too many incorrect attempts”.</dt>
                    <dd>Codes last 5 minutes and allow 5 tries. Go back to the login page and start again for a fresh code.</dd>

                    <dt>A student says the Student ID is not recognised.</dt>
                    <dd>The ID must exist in Zoho exactly as typed (e.g. NL1184). Check it in Zoho; the system only accepts IDs Zoho confirms.</dd>

                    <dt>“Enrolment Location does not match any Branch”.</dt>
                    <dd>Create (or rename) a Branch so its name matches the first word of the student’s Zoho location, then try again.</dd>

                    <dt>A student can’t see an exam.</dt>
                    <dd>Check that the exam is Published, the current time is inside its start–end window, the student’s grade is selected on the exam, the subject matches the student’s Zoho subjects, and attempts aren’t used up.</dd>

                    <dt>I was logged out suddenly.</dt>
                    <dd>Someone signed in to the same account on another device. Log in again and change your password if it wasn’t you.</dd>

                    <dt>Who do I contact for anything else?</dt>
                    <dd>Students/Guardians → their Branch. Teachers → their Branch. Branches → the Super Admin.</dd>
                </dl>
            </section>
        </div>
    </div>
@endsection
