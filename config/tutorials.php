<?php

/*
|--------------------------------------------------------------------------
| Video tutorials
|--------------------------------------------------------------------------
|
| Shown on Super Admin → Video Tutorials, grouped by panel. To add a video,
| put the .mp4 (and an optional .jpg poster) in public/videos/tutorials/
| and add an entry under the right panel. A panel with no videos shows
| "Coming soon".
|
*/

return [
    'panels' => [
        'super_admin' => [
            'label' => 'Super Admin',
            'icon' => 'bi-shield-lock-fill',
            'color' => '#4f46e5',
            'videos' => [
                [
                    'title' => 'Super Admin Panel: complete demo',
                    'description' => 'Login, Branches with the unique User ID login, Grades, Subjects, Question Categories, '
                        .'building an exam with every question type, publishing, results, logs and settings.',
                    'file' => 'videos/tutorials/super-admin-complete-demo.mp4',
                    'poster' => 'videos/tutorials/super-admin-complete-demo.jpg',
                    'duration' => '12:16',
                    'chapters' => [
                        ['0:00', 'Login with email, password and OTP'],
                        ['0:33', 'Branches: User ID, shared email, view, password, deactivate, edit, delete'],
                        ['3:43', 'Grades'],
                        ['4:46', 'Subjects'],
                        ['6:00', 'Question Categories'],
                        ['7:03', 'Create an exam and add questions (normal and Math Equation)'],
                        ['8:25', 'Summary (reading passage) with several questions'],
                        ['9:00', 'Edit a question'],
                        ['9:25', 'Edit the exam'],
                        ['9:58', 'Publish and unpublish'],
                        ['10:42', 'Delete an exam'],
                        ['11:00', 'Results, Logs, User Manual, Settings'],
                        ['11:36', 'Branch login page with User ID'],
                    ],
                ],
            ],
        ],
        'branch' => [
            'label' => 'Branch',
            'icon' => 'bi-building-fill',
            'color' => '#0891b2',
            'videos' => [
                [
                    'title' => 'Branch Panel: login, teachers, exams and results',
                    'description' => 'Branch login with the User ID and OTP, adding, editing and deleting teachers, '
                        .'building an exam with every question type, publishing, and reviewing and sending results.',
                    'file' => 'videos/tutorials/branch-panel.mp4',
                    'poster' => 'videos/tutorials/branch-panel.jpg',
                    'duration' => '5:43',
                    'chapters' => [
                        ['0:08', 'Login with User ID, password and OTP'],
                        ['0:46', 'Branch dashboard'],
                        ['0:54', 'Teachers: add, edit, password, delete'],
                        ['1:56', 'Create an exam'],
                        ['2:39', 'Add questions (normal and Math Equation)'],
                        ['3:06', 'Summary (reading passage) with questions'],
                        ['3:49', 'Publish and unpublish'],
                        ['4:19', 'Delete an exam'],
                        ['4:31', 'Results: review an attempt'],
                        ['4:51', 'Write a review and send the result'],
                        ['5:17', 'Change Password'],
                        ['5:27', 'Logout'],
                    ],
                ],
            ],
        ],
        'teacher' => [
            'label' => 'Teacher',
            'icon' => 'bi-person-workspace',
            'color' => '#059669',
            'videos' => [],
        ],
        'student' => [
            'label' => 'Student',
            'icon' => 'bi-mortarboard-fill',
            'color' => '#d97706',
            'videos' => [
                [
                    'title' => 'Student Panel: login, dashboard and taking an exam',
                    'description' => 'Both student login methods (one time code to the parent\'s email, and Teacher Override), '
                        .'the dashboard tabs and profile, taking an exam with every question type, submitting, and the result.',
                    'file' => 'videos/tutorials/student-panel.mp4',
                    'poster' => 'videos/tutorials/student-panel.jpg',
                    'duration' => '5:57',
                    'chapters' => [
                        ['0:06', 'Login with NRICH Student ID and a one time code'],
                        ['0:58', 'Login with Teacher Override'],
                        ['1:54', 'Dashboard: Available, Upcoming and Completed Exams'],
                        ['2:38', 'My Profile'],
                        ['3:16', 'Open an exam and read the rules'],
                        ['3:41', 'Start the exam and answer questions'],
                        ['4:17', 'Maths questions and text size'],
                        ['4:30', 'Reading passage with several questions'],
                        ['4:41', 'Submit the exam'],
                        ['4:59', 'The result and answer review'],
                        ['5:11', 'Completed Exams and Results'],
                        ['5:42', 'Logout'],
                    ],
                ],
            ],
        ],
    ],
];
