<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MortgageEstimatorService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EstimationRequest;

class MortgageController extends Controller
{
    use ApiResponse;

    public function estimate(Request $request, MortgageEstimatorService $service)
    {
        $validator = Validator::make($request->all(), [
            'property_price' => 'required|numeric|min:10000',
            'deposit' => 'required|numeric|min:0',
            'annual_income' => 'required|numeric|min:1000',
            'term_years' => 'required|integer|min:5|max:40',
            'interest_rate' => 'nullable|numeric|min:0.1|max:20',
            'postcode' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $propertyPrice = (float) $request->property_price;
        $deposit = (float) $request->deposit;

        // Logical validation
        if ($deposit >= $propertyPrice) {
            return $this->validationError([
                'deposit' => ['Deposit must be less than the property price.']
            ]);
        }

        $result = $service->estimateMortgage([
            'property_price' => $propertyPrice,
            'deposit' => $deposit,
            'annual_income' => (float) $request->annual_income,
            'term_years' => (int) $request->term_years,
            'interest_rate' => $request->interest_rate
        ]);

        EstimationRequest::create([
            'estimation_type' => 'mortgage',
            'postcode'        => $request->postcode,
            'address'         => $request->address,
            'input'           => $request->all(),
            'output'          => $result,
        ]);

        return $this->success(
            $result,
            'Mortgage estimate calculated successfully.'
        );
    }
}
