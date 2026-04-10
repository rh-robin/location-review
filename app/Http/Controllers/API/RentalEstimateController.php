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

        // Normalize postcode (remove spaces + uppercase)
        $rawPostcode = strtoupper(str_replace(' ', '', trim($request->postcode)));
        $bedrooms = (int) $request->bedrooms;

        // Basic validation
        if (strlen($rawPostcode) < 5) {
            return $this->validationError([
                'postcode' => ['Invalid postcode format']
            ]);
        }

        // Rebuild standard format
        $outward = substr($rawPostcode, 0, -3);
        $inward  = substr($rawPostcode, -3);

        $postcode = $outward . ' ' . $inward;

        // Extract sector (e.g. SW1A 1AA → SW1A 1)
        $postcodeSector = $outward . ' ' . substr($inward, 0, 1);

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
