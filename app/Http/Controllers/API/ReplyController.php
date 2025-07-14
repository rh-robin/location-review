<?php

namespace App\Http\Controllers\API;

use App\Models\Reply;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReplyController extends Controller
{
    use ApiResponse;

    // Get all replies for a review
    public function index($reviewId)
    {
        $replies = Reply::with('user')
            ->where('review_id', $reviewId)
            ->latest()
            ->get()
            ->map(function ($reply) {
                return [
                    'id' => $reply->id,
                    'review_id' => $reply->review_id,
                    'user_id' => $reply->user_id,
                    'content' => $reply->content,
                    'created_at' => $reply->created_at->format('F j, Y'),
                    'updated_at' => $reply->updated_at->format('F j, Y'),
                    'user' => $reply->user ? [
                        'id' => $reply->user->id,
                        'name' => $reply->user->name,
                        'avatar' => $reply->user->avatar ? asset('storage/'.$reply->user->avatar) : null,
                    ] : null
                ];
            });

        return $this->success(
            data: ['replies' => $replies],
            message: 'Replies retrieved successfully'
        );
    }

    // Store a new reply
    public function store(Request $request)
    {
        $validated = $request->validate([
            'review_id' => 'required|exists:reviews,id',
            'content' => 'required|string|max:2000',
        ]);

        $reply = Reply::create([
            'review_id' => intval($validated['review_id']),
            'user_id' => Auth::id(),
            'content' => $validated['content']
        ]);

        // Load user relationship
        $reply->load('user');

        // Format the user data
        $user = $reply->user;
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
        ];

        // Format the reply data
        $replyData = [
            'id' => $reply->id,
            'review_id' => $reply->review_id,
            'user' => $userData,
            'content' => $reply->content,
            'created_at' => $reply->created_at->format('F j, Y'),
            'updated_at' => $reply->updated_at->format('F j, Y'),
        ];

        return $this->success(
            data: ['reply' => $replyData],
            message: 'Reply created successfully'
        );
    }

    // Update a reply
    public function update(Request $request, $replyId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $user = auth()->user();
        if (!$user) {
            return $this->unauthorized('User not authenticated.');
        }

        $reply = Reply::find($replyId);
        if (!$reply) {
            return $this->notFound('Reply not found.');
        }

        if ($reply->user_id !== $user->id) {
            return $this->unauthorized('You are not authorized to update this reply.');
        }

        $reply->update(['content' => $validated['content']]);

        $reply->load('user');
        $replyData = [
            'id' => $reply->id,
            'review_id' => $reply->review_id,
            'user' => [
                'id' => $reply->user->id,
                'name' => $reply->user->name,
                'avatar' => $reply->user->avatar ? asset('storage/'.$reply->user->avatar) : null,
            ],
            'content' => $reply->content,
            'created_at' => $reply->created_at->format('F j, Y'),
            'updated_at' => $reply->updated_at->format('F j, Y'),
        ];

        return $this->success([
            'reply' => $replyData
        ], 'Reply updated successfully.');
    }

    // Delete a reply
    public function delete($replyId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorized('User not authenticated.');
            }

            $reply = Reply::find($replyId);

            if (!$reply) {
                return $this->notFound('Reply not found.');
            }

            if ($reply->user_id !== $user->id) {
                return $this->unauthorized('You are not authorized to delete this reply.');
            }

            $reply->delete();

            return $this->success(null, 'Reply deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Reply delete error: ' . $e->getMessage());
            return $this->error('Failed to delete reply.', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }
}
