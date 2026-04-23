<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MortgageEstimatorService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EstimationRequest;

class RemortgageController extends Controller
{
    use ApiResponse;

    public function estimate(Request $request, MortgageEstimatorService $service)
    {
        $validator = Validator::make($request->all(), [
            'property_value' => 'required|numeric|min:50000',
            'outstanding_balance' => 'required|numeric|min:1000',
            'current_interest_rate' => 'required|numeric|min:0.1|max:20',
            'new_interest_rate' => 'required|numeric|min:0.1|max:20',
            'remaining_term_years' => 'required|integer|min:5|max:40',
            'postcode' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $propertyValue = (float) $request->property_value;
        $balance = (float) $request->outstanding_balance;

        // Logical validation
        if ($balance > $propertyValue) {
            return $this->validationError([
                'outstanding_balance' => [
                    'Outstanding balance cannot be greater than property value.'
                ]
            ]);
        }

        $result = $service->estimateRemortgage([
            'property_value' => $propertyValue,
            'outstanding_balance' => $balance,
            'current_interest_rate' => (float) $request->current_interest_rate,
            'new_interest_rate' => (float) $request->new_interest_rate,
            'remaining_term_years' => (int) $request->remaining_term_years
        ]);

        EstimationRequest::create([
            'estimation_type' => 'remortgage',
            'postcode'        => $request->postcode,
            'address'         => $request->address,
            'input'           => $request->all(),
            'output'          => $result,
        ]);

        return $this->success(
            $result,
            'Remortgage estimate calculated successfully.'
        );
    }
}
