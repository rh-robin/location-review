<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Review;
use App\Helpers\Helper;
use App\Models\Location;
use App\Models\ReviewImage;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048',
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

            // 2. Find existing review by the user for this location
            $review = Review::where('location_id', $location->id)
                ->where('user_id', $user->id)
                ->first();

            $isNew = false;

            if ($review) {
                // Update existing review
                $review->update([
                    'rating' => $validated['rating'],
                    'comment' => $validated['comment'],
                ]);

                // Delete old images
                ReviewImage::where('review_id', $review->id)->delete();
            } else {
                // Create new review
                $review = Review::create([
                    'location_id' => $location->id,
                    'user_id' => $user->id,
                    'rating' => $validated['rating'],
                    'comment' => $validated['comment'],
                ]);
                $isNew = true;
            }

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

            // 4. Reload review with relations and format the response
            $review->load(['user:id,name,avatar', 'images']);
            $review->like_count = $review->reactions()->where('type', 'like')->count();
            $review->user_reacted = null; // Since this is the current user's own review
            $review->created_at = $review->created_at->format('F j, Y');
            $review->updated_at = $review->updated_at->format('F j, Y');

            $review->images = $review->images->map(function ($image) {
                $image->image = asset($image->image);
                return $image;
            });

            if ($review->user) {
                $review->user->avatar = asset($review->user->avatar);
            }

            return $this->success([
                'review' => $review
            ], $isNew ? 'Review submitted successfully.' : 'Review updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Review store error: ' . $e->getMessage());
            return $this->error('Failed to submit review.', 500, ['error' => $e->getMessage()]);
        }
    }




    /*public function fetchReview(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }
            $latitude = $request->latitude;
            $longitude = $request->longitude;

            $location = Location::where('latitude', $latitude)
                ->where('longitude', $longitude)
                ->first();

            if (!$location) {
                return $this->notFound('Location not found');
            }

            // Fetch reviews with user, images, like count, and the authenticated user's reaction
            $reviews = Review::with(['user:id,name', 'images'])
                ->withCount([
                    'reactions as like_count' => function ($query) {
                        $query->where('type', 'like');
                    }
                ])
                ->with(['reactions' => function ($query) use ($user) {
                    $query->where('user_id', optional($user)->id);
                }])
                ->where('location_id', $location->id)
                ->latest()
                ->get();

            // Append 'user_reacted' key to each review
            $reviews->transform(function ($review) {
                $review->created_at = $review->created_at->format('F j, Y');
                $review->updated_at = $review->updated_at->format('F j, Y');
                $review->images = $review->images->map(function ($image) {
                    $image->image = asset($image->image);
                    return $image;
                });
                $review->user_reacted = optional($review->reactions->first())->type ?? null;
                unset($review->reactions); // remove the reactions array (optional)
                return $review;
            });

            $averageRating = $reviews->avg('rating');

            return $this->success([
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ],
                'average_rating' => round($averageRating, 2),
                'reviews' => $reviews->toArray(),
            ], 'Reviews fetched successfully');

        } catch (\Exception $e) {
            Log::error('Fetch Review Error: ' . $e->getMessage());
            return $this->error('Something went wrong', 500, ['error' => $e->getMessage()]);
        }
    }*/


    public function fetchReview(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            $latitude = $request->latitude;
            $longitude = $request->longitude;

            $location = Location::where('latitude', $latitude)
                ->where('longitude', $longitude)
                ->first();

            if (!$location) {
                return $this->notFound('Location not found');
            }

            // Fetch reviews with user, images, like count, and the authenticated user's reaction
            $reviews = Review::with(['user:id,name,avatar', 'images'])
                ->withCount([
                    'reactions as like_count' => function ($query) {
                        $query->where('type', 'like');
                    }
                ])
                ->with(['reactions' => function ($query) use ($user) {
                    $query->where('user_id', optional($user)->id);
                }])
                ->where('location_id', $location->id)
                ->latest()
                ->get();

            // Transform each review
            $reviews = $reviews->map(function ($review) {
                $reviewData = $review->toArray();

                // Format dates
                $reviewData['created_at'] = \Carbon\Carbon::parse($review->created_at)->format('F j, Y');
                $reviewData['updated_at'] = \Carbon\Carbon::parse($review->updated_at)->format('F j, Y');

                // Format image URLs
                $reviewData['images'] = $review->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'review_id' => $image->review_id,
                        'image' => asset($image->image),
                        'created_at' => \Carbon\Carbon::parse($image->created_at)->format('F j, Y'),
                        'updated_at' => \Carbon\Carbon::parse($image->updated_at)->format('F j, Y'),
                    ];
                });

                // User avatar URL
                if (!empty($review->user)) {
                    $reviewData['user']['avatar'] = $review->user->avatar ? asset($review->user->avatar) : null;
                }

                // Add user reaction
                $reviewData['user_reacted'] = optional($review->reactions->first())->type ?? null;

                // Remove reactions (optional)
                unset($reviewData['reactions']);

                return $reviewData;
            });

            $averageRating = $reviews->avg('rating');

            return $this->success([
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ],
                'average_rating' => round($averageRating, 2),
                'reviews' => $reviews,
            ], 'Reviews fetched successfully');

        } catch (\Exception $e) {
            Log::error('Fetch Review Error: ' . $e->getMessage());
            return $this->error('Something went wrong', 500, ['error' => $e->getMessage()]);
        }
    }

}
