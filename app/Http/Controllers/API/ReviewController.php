<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Review;
use App\Helpers\Helper;
use App\Models\Location;
use App\Models\ReviewImage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    use ApiResponse;

    public function storeReview(Request $request)
    {
        $validated = $request->validate([
            'location_name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'rating' => 'required|integer|in:1,2,3,4,5',
            'comment' => 'required|string|max:2000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }
            DB::beginTransaction();

            // 1. Find or create location
            $location = Location::where('latitude', $validated['latitude'])
                ->where('longitude', $validated['longitude'])
                ->first();

            if (!$location) {
                $location = Location::create([
                    'name' => $validated['location_name'],
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'status' => 'active',
                ]);
            }

            // 2. Create the review
            $review = Review::create([
                'location_id' => $location->id,
                'user_id' => auth()->id(),
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]);

            // 3. Handle review images (if any)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $randomString = Str::random(10);
                    $uploadedPath = Helper::fileUpload($image, 'review_images', $randomString);

                    ReviewImage::create([
                        'review_id' => $review->id,
                        'image' => $uploadedPath,
                    ]);
                }
            }

            DB::commit();

            return $this->success(null, 'Review submitted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Review store error: ' . $e->getMessage());
            return $this->error('Failed to submit review.', 500, ['error' => $e->getMessage()]);
        }
    }

    //review fetch
    public function fetchReview($lat, $lng)
    {
        $reviews = Review::with('location', 'user', 'images', 'replies')->latest()->get();
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
