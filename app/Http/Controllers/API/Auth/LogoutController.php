<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use App\Helpers\Helper;
use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    use ApiResponse;
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorized('User not authenticated.');
        }

        try {
            $user->currentAccessToken()?->delete(); // safe call with null check

            return $this->success(
                data: null,
                message: 'Logged out successfully.'
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Logout failed. Please try again.',
                status: 500,
                errors: ['system_error' => $e->getMessage()]
            );
        }
    }
}
