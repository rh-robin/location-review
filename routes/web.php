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
