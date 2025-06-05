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
            ->get();

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

        // Load user relationship for the response
        $reply->load('user');

       return $this->success(
            data: ['reply' => $reply],
            message: 'Reply created successfully'
       );
    }
}
