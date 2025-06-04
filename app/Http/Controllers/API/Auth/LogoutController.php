<?php

namespace App\Http\Controllers\API\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\Auth;


class LogoutController extends Controller
{
    use ResponseTrait;
    public function logout(): \Illuminate\Http\JsonResponse
    {
        try {
            if (auth()->check()) {
                // For web-based Sanctum auth (cookies)
                Auth::guard('web')->logout();

                // For API token-based auth (recommended for mobile/SPAs)
                auth()->user()->currentAccessToken()->delete();

                return $this->sendResponse('User logged out successfully');
            }

            return $this->sendError('User not authenticated', 401);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
