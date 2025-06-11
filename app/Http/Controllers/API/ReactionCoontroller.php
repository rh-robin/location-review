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

        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorized('User not authenticated.');
            }

            $reaction = Reaction::where('review_id', $validated['review_id'])
                ->where('user_id', $user->id)
                ->first();

            if ($reaction) {
                // If the existing reaction type matches the incoming type, unset it
                if ($reaction->type === $validated['type']) {
                    $reaction->type = null;
                } else {
                    // Otherwise, update the type
                    $reaction->type = $validated['type'];
                }
                $reaction->save();
            } else {
                // Create new reaction
                $reaction = Reaction::create([
                    'review_id' => $validated['review_id'],
                    'user_id' => $user->id,
                    'type' => $validated['type'],
                ]);
            }

            return $this->success($reaction, 'Reaction updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Reaction store error: ' . $e->getMessage());
            return $this->error('Failed to update reaction.', 500, ['error' => $e->getMessage()]);
        }
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
