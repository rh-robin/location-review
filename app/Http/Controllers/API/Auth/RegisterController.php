<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\OtpMail;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    use ApiResponse;
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:150|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $otp = random_int(1000, 9999);
            $otpExpiresAt = Carbon::now()->addMinutes(60);

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
                'is_verified' => false
            ]);
            // Send OTP email
            //Mail::to($user->email)->send(new OtpMail($otp, $user, 'Verify Your Email Address'));
            return $this->success(
                data: $user,
                message: 'Registration successful. Please check your email for verification code.'.$otp
            );
        } catch (Exception $e) {
            Log::error('Register Error', (array)$e->getMessage());
            return $this->error('Something went wrong', 500, $e->getMessage());
        }
    }
    public function VerifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|digits:4',
        ]);

        try {
            $user = User::where('email', $request->input('email'))->first();

            // Check if email has already been verified
            if (!empty($user->email_verified_at)) {
                return $this->error('Email already verified.');
            }
            // Check if OTP code is valid
            if ((string)$user->otp !== (string)$request->input('otp')) {
                return $this->error('Invalid OTP code.');
            }

            // Check if OTP has expired
            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return $this->error('OTP has expired. Please request a new OTP.');
            }

            // Verify the email
            $user->email_verified_at = now();
            $user->is_verified = true;
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->save();

            $token = $user->createToken(env('APP_NAME'))->plainTextToken;

            return $this->success(
                data: [
                    'token' => $token,
                    'user' => $user
                ],
                message: 'Email verified successfully.'
            );

        } catch (Exception $e) {
            return $this->error('Something went wrong', 500, $e->getMessage());
        }

    }

    public function ResendOtp(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        try {
            $user = User::where('email', $request->input('email'))->first();
            if (!$user) {
                return  $this->notFound('User not found.');
            }

            if ($user->email_verified_at) {
                return  $this->error('Email already verified.');
            }

            $newOtp               = random_int(1000, 9999);
            $otpExpiresAt         = Carbon::now()->addMinutes(60);
            $user->otp            = $newOtp;
            $user->otp_expires_at = $otpExpiresAt;
            $user->save();
            //Mail::to($user->email)->send(new OtpMail($newOtp, $user, 'Verify Your Email Address'));

            return  $this->success($user, 'OTP sent successfully.'.$newOtp);
        } catch (Exception $e) {
            return  $this->error(
                'Something went wrong',
                500,
            );
        }
    }
}
