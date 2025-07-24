<?php

namespace App\Http\Controllers\API;

use App\Mail\ReviewReportedMail;
use App\Models\Report;
use App\Helpers\Helper;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        $review = Review::find($request->review_id);
        $reviewer = $review->user;
        $location = $review->location;

        // Create or update report
        $report = Report::updateOrCreate(
            [
                'review_id' => intval($validated['review_id']),
                'user_id' => Auth::id(),
            ],
            [
                'reason' => $validated['reason'],
                'description' => $validated['description'],
                'image' => $imagePath,
            ]
        );
        if ($report) {
            $report->image = $report->image ? asset('storage/' . $report->image) : null;

            // 🔔 Send email to review author
            if ($reviewer && $reviewer->email) {
                Mail::to($reviewer->email)->send(new ReviewReportedMail($report));
            }
        }

        return $this->success(
            data: $report,
            message: 'Report submitted successfully'
        );
    }

    public function update(Request $request, $reportId)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048',
            'deleted_images' => 'nullable|array',
            'deleted_images.*' => 'integer|exists:reports,id', // Adjusted to reports table
        ]);

        try {
            $user = Auth::user();
            if (!$user) {
                return $this->unauthorized('User not authenticated.');
            }

            $report = Report::find($reportId);

            if (!$report) {
                return $this->notFound('Report not found.');
            }

            if ($report->user_id !== $user->id) {
                return $this->unauthorized('You are not authorized to update this report.');
            }

            DB::beginTransaction();

            // 1. Update report content
            $report->update([
                'reason' => $validated['reason'],
                'description' => $validated['description'],
            ]);

            // 2. Delete existing image if requested
            if (!empty($validated['deleted_images']) && in_array($report->id, $validated['deleted_images']) && $report->image) {
                Helper::fileDelete(public_path('storage/' . $report->image));
                $report->update(['image' => null]);
            }

            // 3. Upload new image
            if ($request->hasFile('image')) {
                // Delete existing image if present
                if ($report->image) {
                    Helper::fileDelete(public_path('storage/' . $report->image));
                }
                $file = $request->file('image');
                $fileName = Str::random(10);
                $imagePath = Helper::fileUpload($file, 'reports', $fileName);
                $report->update(['image' => $imagePath]);
            }

            DB::commit();

            // 4. Format response
            $report->image = $report->image ? asset('storage/' . $report->image) : null;

            return $this->success([
                'report' => $report
            ], 'Report updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Report update error: ' . $e->getMessage());

            return $this->error('Failed to update report.', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function delete($reportId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->unauthorized('User not authenticated.');
            }

            $report = Report::find($reportId);

            if (!$report) {
                return $this->notFound('Report not found.');
            }

            if ($report->user_id !== $user->id) {
                return $this->unauthorized('You are not authorized to delete this report.');
            }

            DB::beginTransaction();

            // Delete associated image
            if ($report->image) {
                Helper::fileDelete(public_path('storage/' . $report->image));
            }

            // Delete the report
            $report->delete();

            DB::commit();

            return $this->success(null, 'Report deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Report delete error: ' . $e->getMessage());

            return $this->error('Failed to delete report.', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }
}
