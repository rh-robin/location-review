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

class ReviewController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required_without:latitude,longitude|exists:locations,id',
            'current_location' => 'required_without:location_id|string|max:255',
            'latitude' => 'required_without:location_id|numeric',
            'longitude' => 'required_without:location_id|numeric',
            'rating' => 'required|integer|in:1,2,3,4,5',
            'comment' => 'required|string|max:2000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048',
        ]);

        try {
            DB::beginTransaction();
            // Handle location
            if (!isset($validated['location_id'])) {
                $latitude = round($validated['latitude'], 6);
                $longitude = round($validated['longitude'], 6);

                // Check if coordinates exist
                $location = Location::where('latitude', $latitude)
                    ->where('longitude', $longitude)
                    ->first();

                if (!$location) {
                    // Create new location if it doesn't exist
                    $location = Location::create([
                        'user_id' => Auth::id(),
                        'current_location' => $validated['current_location'],
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'status' => 'active'
                    ]);
                }
                $validated['location_id'] = $location->id;
            }

            // Rest of your store method (review creation, image handling, etc.)
            $review = Review::firstOrNew(
                [
                    'location_id' => $validated['location_id'],
                    'user_id' => Auth::id()
                ],
                [
                    'rating' => intval($validated['rating']),
                    'comment' => $validated['comment']
                ]
            );

            $isUpdate = $review->exists;
            $review->fill([
                'rating' => intval($validated['rating']),
                'comment' => $validated['comment']
            ])->save();

            if ($request->hasFile('images')) {
                if ($isUpdate) {
                    ReviewImage::where('review_id', $review->id)->delete();
                }
                foreach ($request->file('images') as $image) {
                    $path = Helper::fileUpload($image, 'reviews', $image->getClientOriginalName());
                    ReviewImage::create([
                        'review_id' => $review->id,
                        'image' => asset($path),
                    ]);
                }
            }

            DB::commit();

            return $this->success(
                data: $review->load(['images', 'location']),
                message: $isUpdate ? 'Review updated successfully' : 'Review created successfully'
            );
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Review store error: ' . $e->getMessage());
            return $this->error(
                message: 'Failed to process review due to a database error',
                status: 500
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Review store error: ' . $e->getMessage());
            return $this->error(
                message: 'An unexpected error occurred',
                status: 500
            );
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
