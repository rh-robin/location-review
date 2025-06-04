<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;


class LoginController extends Controller
{
    use ApiResponse;

    public function Login(Request $request): \Illuminate\Http\JsonResponse
    {

        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            if (filter_var($request->email, FILTER_VALIDATE_EMAIL) !== false) {
                $user = User::where('email', $request->email)->first();
                if (empty($user)) {
                    return $this->notFound('User not found.');
                }
            }
            // Check the password
            if (!Hash::check($request->password, $user->password)) {
                return $this->error('Invalid credentials. Please check your email and password.');
            }

            // Check if the email is verified before login is successful
            if (!$user->email_verified_at) {
                return $this->unauthorized('Email is not verified. Please verify your email first.');
            }

            // Generate token if email is verified
            $token = $user->createToken('YourAppName')->plainTextToken;

            return $this->success(
                data: [
                    'token_type' => 'bearer',
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_verified' => $user->is_verified,
                    ],
                ],
                message: 'Login successful.'
            );
        } catch (Exception $e) {
            return $this->error('Login failed. Please try again.', 500, ['system_error' => $e->getMessage()]);
        }
    }
}
