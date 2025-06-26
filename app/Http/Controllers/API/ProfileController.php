<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Reply;
use App\Models\Report;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\ResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    use ApiResponse;

    public function getPersonalInfo()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorized('User not authenticated.');
            }

            $profile = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'location' => $user->location,
                'avatar' => $user->avatar === null ? null : asset('storage/'.$user->avatar),
            ];

            return $this->success($profile, 'User profile retrieved successfully.');

        } catch (\Exception $e) {
            \Log::error('Get Profile Error', ['error' => $e->getMessage()]);
            return $this->error('Something went wrong while fetching profile.', 500, ['error' => $e->getMessage()]);
        }
    }


    public function updatePersonalInfo(Request $request)
    {
        try {
            $user = auth()->user(); // Sanctum uses the default web guard unless configured

            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'location' => 'nullable|string|max:200',
            ]);

            $user->update($validatedData);

            $updatedProfile = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'location' => $user->location,
            ];

            return $this->success($updatedProfile, 'Profile updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (\Exception $e) {
            \Log::error('Update Profile Error', ['error' => $e->getMessage()]);
            return $this->error('Something went wrong while updating profile', 500, ['error' => $e->getMessage()]);
        }
    }


    public function changeAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            // Delete old avatar if exists
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                Helper::fileDelete(public_path($user->avatar));
            }

            // Upload new avatar using the same helper
            $randomString = Str::random(10);
            $uploadedPath = Helper::fileUpload($request->file('avatar'), 'avatars', $randomString);

            // Save to DB
            $user->avatar = $uploadedPath;
            $user->save();

            return $this->success([
                'message' => 'Avatar updated successfully',
                'avatar_url' => asset('storage/'.$user->avatar),
            ]);
        } catch (\Exception $e) {
            Log::error('Avatar Update Error: ' . $e->getMessage());
            return $this->error('Something went wrong while updating avatar', 500, ['error' => $e->getMessage()]);
        }
    }



    public function changePassword(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            $validated = $request->validate([
                'current_password' => 'required|string|min:6',
                'new_password' => 'required|string|min:8',
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->error('Current password is incorrect', 403);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            return $this->success(null, 'Password changed successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (\Exception $e) {
            \Log::error('Change Password Error', ['error' => $e->getMessage()]);
            return $this->error('Something went wrong while changing password', 500, ['error' => $e->getMessage()]);
        }
    }



    public function getMyReviews()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            // Fetch all reviews of the authenticated user with location, images, and like count
            $reviews = Review::with(['location', 'images'])
                ->withCount([
                    'reactions as like_count' => function ($query) {
                        $query->where('type', 'like');
                    }
                ])
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            // Transform each review
            $transformedReviews = $reviews->map(function ($review) {
                $reviewData = [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'like_count' => $review->like_count,
                    'created_at' => \Carbon\Carbon::parse($review->created_at)->format('F j, Y'),
                    'updated_at' => \Carbon\Carbon::parse($review->updated_at)->format('F j, Y'),
                ];

                // Review Images
                $images = $review->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_url' => asset($image->image),
                        'created_at' => \Carbon\Carbon::parse($image->created_at)->format('F j, Y'),
                    ];
                });

                // Location Info
                $location = $review->location;
                $locationData = $location ? [
                    'id' => $location->id,
                    'name' => $location->name,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ] : null;

                return [
                    'location' => $locationData,
                    'review' => $reviewData,
                    'images' => $images,
                ];
            });

            return $this->success([
                'reviews' => $transformedReviews
            ], 'User reviews fetched successfully');

        } catch (\Exception $e) {
            Log::error('Get My Reviews Error: ' . $e->getMessage());
            return $this->error('Failed to fetch user reviews', 500, ['error' => $e->getMessage()]);
        }
    }


    public function getMyReplies(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            // Fetch replies with related review and location
            $replies = Reply::with([
                'review' => function ($query) {
                    $query->select('id', 'location_id', 'user_id', 'rating', 'comment', 'created_at')
                        ->with('location:id,name,latitude,longitude');
                }
            ])
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            // Transform each reply
            $transformedReplies = $replies->map(function ($reply) use ($user) {
                $review = $reply->review;
                $location = $review?->location;

                return [
                    'reply_id' => $reply->id,
                    'review_id' => $reply->review_id,
                    'content' => $reply->content,
                    'created_at' => $reply->created_at->format('F j, Y'),
                    'updated_at' => $reply->updated_at->format('F j, Y'),
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->avatar ? asset($user->avatar) : null,
                    ],
                    'review' => $review ? [
                        'id' => $review->id,
                        'comment' => $review->comment,
                        'rating' => $review->rating,
                        'created_at' => $review->created_at->format('F j, Y'),
                    ] : null,
                    'location' => $location ? [
                        'id' => $location->id,
                        'name' => $location->name,
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                    ] : null,
                ];
            });

            return $this->success([
                'replies' => $transformedReplies
            ], 'Replies fetched successfully');

        } catch (\Exception $e) {
            Log::error('Get My Replies Error: ' . $e->getMessage());
            return $this->error('Failed to fetch replies', 500, ['error' => $e->getMessage()]);
        }
    }


    public function getMyReports()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated.');
            }

            // Fetch reports with related review, location, and replies
            $reports = Report::with([
                'review.location',
                'replies.user:id,name,avatar',
            ])
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            // Format each report
            $transformedReports = $reports->map(function ($report) {
                $review = $report->review;

                // Format replies
                $replies = $report->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'report_id' => $reply->report_id,
                        'reply' => $reply->reply,
                        'user' => [
                            'id' => $reply->user->id,
                            'name' => $reply->user->name,
                            'avatar' => $reply->user->avatar ? asset($reply->user->avatar) : null,
                        ],
                        'created_at' => $reply->created_at->format('F j, Y'),
                    ];
                });

                return [
                    'report_id' => $report->id,
                    'reason' => $report->reason,
                    'description' => $report->description,
                    'image_url' => $report->image ? asset($report->image) : null,
                    'status' => $report->status,
                    'created_at' => $report->created_at->format('F j, Y'),

                    'review' => $review ? [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at->format('F j, Y'),
                        'location' => $review->location ? [
                            'id' => $review->location->id,
                            'name' => $review->location->name,
                            'latitude' => $review->location->latitude,
                            'longitude' => $review->location->longitude,
                        ] : null,
                    ] : null,

                    'replies' => $replies,
                ];
            });

            return $this->success(
                data: ['reports' => $transformedReports],
                message: 'User reports fetched successfully.'
            );

        } catch (\Exception $e) {
            \Log::error('My Reports Error', ['error' => $e->getMessage()]);
            return $this->error(
                message: 'Failed to fetch reports.',
                status: 500,
                errors: ['system_error' => $e->getMessage()]
            );
        }
    }





}
