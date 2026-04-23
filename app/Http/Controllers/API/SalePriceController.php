<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SalePriceEstimatorService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\EstimationRequest;

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
            'address'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Normalize postcode (remove spaces, uppercase)
        $rawPostcode = strtoupper(str_replace(' ', '', trim($request->postcode)));

        // Basic length check (UK postcodes usually 5–7 chars)
        if (strlen($rawPostcode) < 5) {
            return $this->validationError(['postcode' => ['Invalid postcode format']]);
        }

        // Rebuild standard format (insert space before last 3 chars)
        $outward = substr($rawPostcode, 0, -3);
        $inward  = substr($rawPostcode, -3);

        $postcode = $outward . ' ' . $inward;

        // Extract sector (e.g. SW1A 1AA → SW1A 1)
        $postcodeSector = $outward . ' ' . substr($inward, 0, 1);

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

        EstimationRequest::create([
            'estimation_type' => 'sale',
            'postcode'        => $postcode,
            'address'         => $request->address,
            'input'           => $request->all(),
            'output'          => $result,
        ]);

        return $this->success($result, 'Estimated sale price calculated successfully.');
    }
}
