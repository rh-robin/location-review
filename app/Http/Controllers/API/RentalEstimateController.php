<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\RentalEstimatorService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RentalEstimateController extends Controller
{
    use ApiResponse;

    public function estimate(Request $request, RentalEstimatorService $service)
    {
        $validator = Validator::make($request->all(), [
            'postcode' => 'required|string',
            'bedrooms' => 'required|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $postcode = strtoupper(trim($request->postcode));
        $bedrooms = (int) $request->bedrooms;

        // Extract postcode sector
        $parts = explode(' ', $postcode);

        if (count($parts) < 2) {
            return $this->validationError([
                'postcode' => ['Invalid postcode format']
            ]);
        }

        $postcodeSector = $parts[0] . ' ' . substr($parts[1], 0, 1);

        // Lookup district from property_sales
        $location = DB::table('property_sales')
            ->where('postcode_sector', $postcodeSector)
            ->select('district')
            ->first();

        if (!$location || !$location->district) {
            return $this->notFound('Location not found for given postcode.');
        }

        $result = $service->estimate([
            'postcode_sector' => $postcodeSector,
            'district' => $location->district,
            'bedrooms' => $bedrooms,
        ]);

        if (!$result['estimated_rent']) {
            return $this->notFound('Rental data not available for this area.');
        }

        return $this->success($result, 'Estimated monthly rent calculated successfully.');
    }
}
