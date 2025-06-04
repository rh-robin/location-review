<?php

namespace App\Traits;


trait ApiResponse
{
    protected function success(mixed $data = null, string $message = '', int $status = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error response.
     */
    protected function error(string $message = 'Something went wrong', int $status = 500, mixed $errors = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Return a validation error response.
     */
    protected function validationError(array $errors, string $message = 'Validation failed')
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Return an unauthorized error response.
     */
    protected function unauthorized(string $message = 'Unauthorized')
    {
        return $this->error($message, 401);
    }

    /**
     * Return a not found error response.
     */
    protected function notFound(string $message = 'Data not found')
    {
        return $this->error($message, 404);
    }
}

