<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Location;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class LocationController extends Controller
{
    use ApiResponse;
    //store location
    public function StoreLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        try {
            $location = new Location();
            $location->latitude = floatval($request->latitude) ;
            $location->longitude = floatval($request->longitude);
            $location->user_id = auth()->user()->id;
            $location->save();
            return $this->success(
                data: $location,
                message: 'Location saved successfully.'
            );
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(
                message: 'Something went wrong, please try again later.'
            );
        }
    }
}
