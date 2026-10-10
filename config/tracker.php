<?php

return [
    /*
    | First administrator, used only by `php artisan tracker:install` when it runs
    | without a keyboard (for example as a Laravel Cloud deploy command).
    | Remove ADMIN_PASSWORD from the environment after the first deploy.
    */
    'admin' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'employee_id' => env('ADMIN_EMPLOYEE_ID'),
        'job_title' => env('ADMIN_JOB_TITLE', 'Support Lead'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
