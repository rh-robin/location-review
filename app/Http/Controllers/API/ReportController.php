<?php

namespace App\Http\Controllers\API;

use App\Models\Report;
use App\Helpers\Helper;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'review_id' => 'required|exists:reviews,id',
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048',
        ]);

        $imagePath = null;

        // Handle single image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = Str::random(10);
            $imagePath = Helper::fileUpload($file, 'reports', $fileName);
        }

        // Create or update report
        $report = Report::updateOrCreate(
            [
                'review_id' => intval($validated['review_id']),
                'user_id' => Auth::id(),
            ],
            [
                'reason' => $validated['reason'],
                'description' => $validated['description'],
                'image' => asset($imagePath),
            ]
        );

        return $this->success(
            data: $report,
            message: 'Report submitted successfully'
        );
    }
}
