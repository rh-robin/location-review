<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\ResponseTrait;

class ContactController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'topic'   => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        try {
            $contact = Contact::create($validated);

            return $this->success(
                data: ['contact' => $contact],
                message: 'Your message has been received successfully.'
            );

        } catch (\Exception $e) {
            \Log::error('Contact Store Error', ['error' => $e->getMessage()]);

            return $this->error(
                message: 'Failed to submit message.',
                status: 500,
                errors: ['system_error' => $e->getMessage()]
            );
        }
    }
}
