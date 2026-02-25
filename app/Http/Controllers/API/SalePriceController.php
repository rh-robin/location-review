<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SalePriceEstimatorService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalePriceController extends Controller
{
    use ApiResponse;

    public function estimate(Request $request, SalePriceEstimatorService $service)
    {
        $validator = Validator::make($request->all(), [
            'postcode'       => 'required|string',
            'property_type'  => 'required|in:D,S,T,F',
            'duration'       => 'required|in:F,L',
            'months'         => 'nullable|integer|min:3|max:24',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $postcode = strtoupper(trim($request->postcode));

        // Extract sector
        $parts = explode(' ', $postcode);
        if (count($parts) < 2) {
            return $this->validationError(['postcode' => ['Invalid postcode format']]);
        }

        $postcodeSector = $parts[0] . ' ' . substr($parts[1], 0, 1);

        // Fetch district & county once
        $location = DB::table('property_sales')
            ->where('postcode_sector', $postcodeSector)
            ->select('district', 'county')
            ->first();

        if (!$location) {
            return $this->notFound('Location data not found for given postcode.');
        }

        $result = $service->estimate([
            'postcode_sector' => $postcodeSector,
            'district'        => $location->district,
            'county'          => $location->county,
            'property_type'   => $request->property_type,
            'duration'        => $request->duration,
            'months'          => $request->months ?? 6,
        ]);

        if (!$result['estimated_price']) {
            return $this->notFound('No comparable sales found.');
        }

        return $this->success($result, 'Estimated sale price calculated successfully.');
    }
}
