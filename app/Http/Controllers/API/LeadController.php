<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    use ApiResponse;

    /**
     * Save "Save my plan" lead.
     */
    public function savePlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'              => 'required|email',
            'planning_time'      => 'required|in:asap,1-3 months,3-6 months',
            'note'               => 'nullable|string',
            'source'             => 'required|in:sales,rent,mortgage,remortgage',
            'user_role'          => 'required_if:source,rent|nullable',
            'calculation_input'  => 'required|array',
            'calculation_output' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $lead = Lead::create([
            'type'               => 'plan',
            'source'             => $request->source,
            'user_role'          => strtolower($request->user_role),
            'email'              => $request->email,
            'planning_time'      => $request->planning_time,
            'note'               => $request->note,
            'calculation_input'  => $request->calculation_input,
            'calculation_output' => $request->calculation_output,
        ]);

        return $this->success($lead, 'Plan saved successfully.', 201);
    }

    /**
     * Save "Request expert consultation" lead.
     */
    public function saveExpertConsultation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number_of_experts'       => 'required|integer|in:1,2,3',
            'name'                    => 'required|string|max:255',
            'preferred_contact_method' => 'required|in:call,email',
            'phone'                   => 'required|string|max:20',
            'address'                 => 'required|string',
            'email'                   => 'nullable|email',
            'preferred_date'          => 'required|date',
            'preferred_time'          => 'required|string',
            'source'                  => 'required|in:sales,rent,mortgage,remortgage',
            'user_role'               => 'required_if:source,rent|nullable',
            'calculation_input'       => 'required|array',
            'calculation_output'      => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $lead = Lead::create([
            'type'                     => 'consultation',
            'source'                   => $request->source,
            'user_role'                => strtolower($request->user_role),
            'number_of_experts'        => $request->number_of_experts,
            'name'                     => $request->name,
            'preferred_contact_method'  => $request->preferred_contact_method,
            'phone'                    => $request->phone,
            'address'                  => $request->address,
            'email'                    => $request->email,
            'preferred_date'           => $request->preferred_date,
            'preferred_time'           => $request->preferred_time,
            'calculation_input'        => $request->calculation_input,
            'calculation_output'       => $request->calculation_output,
        ]);

        return $this->success($lead, 'Consultation request submitted successfully.', 201);
    }
}
