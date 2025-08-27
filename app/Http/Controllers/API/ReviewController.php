<?php

namespace App\Http\Controllers\API;

use App\Models\UserLocation;
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
            'role' => 'nullable|in:former_tenant,current_tenant,landlord',
        ]);

        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            DB::beginTransaction();

            // 1. Find or create location
            $location = Location::firstOrCreate(
                [
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                ],
                [
                    'name' => $validated['location_name'],
                    'status' => 'active',
                ]
            );

            // 2. Store user-location role only if role is provided
            if ($validated['role']) {
                UserLocation::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'location_id' => $location->id,
                    ],
                    [
                        'role' => $validated['role'],
                    ]
                );
            }

            // 3. Always create a new review
            $review = Review::create([
                'location_id' => $location->id,
                'user_id' => $user->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]);

            // 4. Handle review images (if any)
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

            // 5. Load relationships and format response
            $review->load(['user:id,name,avatar', 'images']);
            $review->like_count = $review->reactions()->where('type', 'like')->count();
            $review->user_reacted = null; // For own review
            $review->created_at = $review->created_at->format('F j, Y');
            $review->updated_at = $review->updated_at->format('F j, Y');

            $review->images = $review->images->map(function ($image) {
                $image->image = asset('storage/' . $image->image);
                return $image;
            });

            if ($review->user) {
                $review->user->avatar = $review->user->avatar ? asset('storage/' . $review->user->avatar) : null;
            }

            return $this->success([
                'review' => $review
            ], 'Review submitted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Review store error: ' . $e->getMessage());
            return $this->error('Failed to submit review.', 500, ['error' => $e->getMessage()]);
        }
    }


    /*============ update review ==============*/
    public function updateReview(Request $request, $reviewId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|in:1,2,3,4,5',
            'comment' => 'required|string|max:2000',
            'deleted_images' => 'nullable|array',
            'deleted_images.*' => 'integer|exists:review_images,id',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048',
            'role' => 'nullable|in:former_tenant,current_tenant,landlord',
        ]);

        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated.');
            }

            $review = Review::with('images')->find($reviewId);

            if (!$review) {
                return $this->notFound('Review not found.');
            }

            if ($review->user_id !== $user->id) {
                return $this->unauthorized('You are not authorized to update this review.');
            }

            DB::beginTransaction();

            // 1. Update user-location role only if role is provided
            if ($validated['role']) {
                UserLocation::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'location_id' => $review->location_id,
                    ],
                    [
                        'role' => $validated['role'],
                    ]
                );
            }

            // 2. Update review content
            $review->update([
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]);

            // 3. Delete selected images
            if (!empty($validated['deleted_images'])) {
                $imagesToDelete = ReviewImage::whereIn('id', $validated['deleted_images'])->get();

                foreach ($imagesToDelete as $img) {
                    Helper::fileDelete(public_path('storage/' . $img->image));
                    $img->delete();
                }
            }

            // 4. Upload new images
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

            // 5. Load relationships and format response
            $review->load(['user:id,name,avatar', 'images']);
            $review->like_count = $review->reactions()->where('type', 'like')->count();
            $review->user_reacted = null;
            $review->created_at = $review->created_at->format('F j, Y');
            $review->updated_at = $review->updated_at->format('F j, Y');

            $review->images = $review->images->map(function ($image) {
                $image->image = asset('storage/' . $image->image);
                return $image;
            });

            if ($review->user) {
                $review->user->avatar = $review->user->avatar ? asset('storage/' . $review->user->avatar) : null;
            }

            return $this->success([
                'review' => $review
            ], 'Review updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Review update error: ' . $e->getMessage());

            return $this->error('Failed to update review.', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }



    public function fetchReview(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'date' => 'nullable|in:newest,oldest', // Add validation for optional date input
        ]);

        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $sortOrder = $request->date === 'oldest' ? 'asc' : 'desc'; // Default is newest

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
                ->orderBy('updated_at', $sortOrder) // Apply sorting by updated_at
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
                        'image' => asset('storage/' . $image->image),
                        'created_at' => \Carbon\Carbon::parse($image->created_at)->format('F j, Y'),
                        'updated_at' => \Carbon\Carbon::parse($image->updated_at)->format('F j, Y'),
                    ];
                });

                // User avatar URL
                if (!empty($review->user)) {
                    $reviewData['user']['avatar'] = $review->user->avatar ? asset('storage/' . $review->user->avatar) : null;
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



    public function deleteReview($reviewId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated.');
            }

            $review = Review::with('images')->find($reviewId);

            if (!$review) {
                return $this->notFound('Review not found.');
            }

            if ($review->user_id !== $user->id) {
                return $this->unauthorized('You are not authorized to delete this review.');
            }

            DB::beginTransaction();

            // Delete all associated images
            foreach ($review->images as $image) {
                Helper::fileDelete(public_path('storage/' . $image->image));
                $image->delete();
            }

            // Delete the review itself
            $review->delete();

            DB::commit();

            return $this->success(null, 'Review deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Review delete error: ' . $e->getMessage());

            return $this->error('Failed to delete review.', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }



}
