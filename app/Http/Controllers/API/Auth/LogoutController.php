<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use App\Helpers\Helper;
use App\Traits\ApiResponse;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;


class LogoutController extends Controller
{
    use ApiResponse;
    public function logout(): \Illuminate\Http\JsonResponse
    {
        try {
            if (auth()->check()) {
                // For web-based Sanctum auth (cookies)
                Auth::guard('web')->logout();

                // For API token-based auth (recommended for mobile/SPAs)
                auth()->user()->currentAccessToken()->delete();

                return $this->success('User logged out successfully');
            }

            return $this->error('User not authenticated');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
