<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\OtpMail;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ResetPasswordController extends Controller
{
    use ApiResponse;

    public function forgotPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        try {
            $email = $request->input('email');
            $otp = random_int(1000, 9999);
            $user = User::where('email', $email)->first();

            if (!$user) {
                return $this->error(
                    'User account not found.'
                );
            }

            Mail::to($email)->send(new OtpMail($otp, $user, 'Reset Your Password'));

            $user->update([
                'otp' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(60),
            ]);

            $user->makeHidden('otp');

            return $this->success(
                data: $user,
                message: 'OTP sent successfully.'
            );

        } catch (Exception $e) {
            return $this->error(
                'Something went wrong',
                500
            );
        }
    }

    public function VerifyOTP(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:4',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->notFound(
                    'User account not found.'
                );
            }

            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return $this->error(
                    'OTP has expired. Please request a new OTP.'
                );
            }

            if ($user->otp !== $request->otp) {
                return $this->error(
                    'Invalid OTP code.'
                );
            }

            $token = Str::random(60);
            $user->update([
                'otp' => null,
                'otp_expires_at' => null,
                'reset_password_token' => $token,
                'reset_password_token_expire_at' => Carbon::now()->addHour(),
            ]);

            return $this->success(
                data: $user,
                message: 'OTP verified successfully.'
            );

        } catch (Exception $e) {
            return $this->error(
                'Something went wrong',
                500
            );
        }
    }

    public function ResetPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->notFound(
                    'User account not found.'
                );
            }

            $tokenValid = $user->reset_password_token === $request->token &&
                $user->reset_password_token_expire_at >= Carbon::now();

            if (!$tokenValid) {
                return $this->error(
                    'Invalid reset password token.'
                );
            }

            $user->update([
                'password' => Hash::make($request->password),
                'reset_password_token' => null,
                'reset_password_token_expire_at' => null,
            ]);

            return $this->success(
                data: $user,
                message: 'Password reset successfully.'
            );

        } catch (Exception $e) {
            return $this->error(
                'Something went wrong',
                500
            );
        }
    }
}
