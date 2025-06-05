<?php

namespace App\Http\Controllers\API;

use App\Models\Reaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReactionCoontroller extends Controller
{
    use ApiResponse;
    // Store or update a reaction
    public function store(Request $request)
    {
        $validated = $request->validate([
            'review_id' => 'required|exists:reviews,id',
            'type' => 'required|in:like,dislike',
        ]);

        $user = Auth::user();

        // Find existing reaction or create new one
        $reaction = Reaction::updateOrCreate(
            [
                'review_id' => $validated['review_id'],
                'user_id' => $user->id,
            ],
            [
                'type' => $validated['type'] === 'dislike' ? 'dislike' : 'like',
            ]
        );

        return $this->success(
            message: 'Reaction saved successfully',
            data: ['reaction' => $reaction]
        );
    }

    // Get reaction counts for a review
    public function getCounts($reviewId)
    {
        $counts = Reaction::where('review_id', $reviewId)
            ->selectRaw('count(case when type = "like" then 1 end) as likes')
            ->selectRaw('count(case when type = "dislike" then 1 end) as dislikes')
            ->first();

        return $this->success(
            data: $counts,
            message: 'Reaction counts retrieved successfully'
        );
    }

    // Get user's reaction for a specific review
    public function getUserReaction($reviewId)
    {
        $reaction = Reaction::where([
            'review_id' => $reviewId,
            'user_id' => Auth::id(),
        ])->first();

        return $this->success(
            data: ['reaction' => $reaction],
            message: 'User reaction retrieved successfully'
        );
    }
}
