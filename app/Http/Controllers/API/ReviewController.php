<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Review;
use App\Helpers\Helper;
use App\Models\ReviewImage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:2000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Find or create the review
            $review = Review::updateOrCreate(
                ['location_id' => (int) $validated['location_id'], 'user_id' => Auth::id()],
                ['rating' => (int) $validated['rating'], 'comment' => $validated['comment']]
            );
            // Store new images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = Helper::fileUpload($image, 'reviews', $image->getClientOriginalName());

                    ReviewImage::create([
                        'review_id' => $review->id,
                        'image' => asset($path),
                    ]);
                }
            }

            return $this->success(
                data: $review->load('images'),
                message: 'Review updated successfully'
            );
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Something went wrong', 500, $e->getMessage());
        }
    }

    //review fetch
    public function fetchReview(Request $request)
    {
        $reviews = Review::with('location', 'user', 'images','replies')->latest()->get();
        //total reaction spasic review
        $reviews->map(function ($review) {
            $review->total_reactions = $review->reactions()->count();
            $review->total_likes = $review->reactions()->where('type', 'like')->count();
            $review->total_dislikes = $review->reactions()->where('type', 'dislike')->count();
        });
        //ratting  int formate
        $reviews->map(function ($review) {
            $review->rating = (int) $review->rating;
        });
        return $this->success(data: $reviews, message: 'Reviews fetched successfully');

    }
}
