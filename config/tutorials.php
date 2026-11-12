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
            'videos' => [],
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
            'videos' => [],
        ],
    ],
];
