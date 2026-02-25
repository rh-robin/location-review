<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;


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
                return $this->error('Invalid credentials. Please try again.');
            }

            // Check if the email is verified before login is successful
            if (!$user->email_verified_at) {
                return $this->error('Email is not verified. Please verify your email first.', 401, ["is_verified" => false]);
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


    public function socialLogin(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'token'    => 'required|string',
            'provider' => 'required|in:google',
        ]);

        try {
            $provider = $request->provider;

            // Get user info from the social provider using token
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($request->token);

            if (! $socialUser || ! $socialUser->getEmail()) {
                return $this->error('Invalid credentials from provider.', 401);
            }

            // Try to find the user by email or provider_id
            $user = User::where('email', $socialUser->getEmail())
                ->orWhere(function ($query) use ($provider, $socialUser) {
                    $query->where('provider', $provider)
                        ->where('provider_id', $socialUser->getId());
                })
                ->first();

            $isNewUser = false;

            // If no user exists, register a new one
            if (! $user) {
                $user = User::create([
                    'name'              => $socialUser->getName() ?? 'Unknown',
                    'email'             => $socialUser->getEmail(),
                    'password'          => bcrypt(Str::random(16)),
                    'provider'          => $provider,
                    'provider_id'       => $socialUser->getId(),
                    'email_verified_at' => now(),
                    'is_verified'       => true,
                    'user_type'         => 'user',
                ]);

                $isNewUser = true;
            }

            // Create Sanctum token
            $token = $user->createToken('YourAppName')->plainTextToken;

            // Prepare response
            return $this->success(
                data: [
                    'token_type' => 'bearer',
                    'token' => $token,
                    'user'  => [
                        'id'         => $user->id,
                        'name'       => $user->name,
                        'email'      => $user->email,
                        'is_verified'=> $user->email_verified_at !== null,
                        'user_type'  => $user->user_type,
                    ],
                ],
                message: $isNewUser ? 'User registered and logged in successfully.' : 'Login successful.'
            );

        } catch (\Exception $e) {
            return $this->error('Social login failed. Please try again.', 500, ['system_error' => $e->getMessage()]);
        }
    }
}
