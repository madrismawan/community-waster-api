<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    public function success(
        mixed $data = null,
        string $message = 'Request completed successfully.',
        int $status = Response::HTTP_OK,
        ?array $meta = null,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?? (object) [],
            'meta' => $meta ?: (object) [],
            'errors' => null,
        ], $status);
    }

    public function error(
        string $message,
        int $status,
        ?array $errors = null,
        array $headers = [],
        ?array $meta = null,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => (object) [],
            'meta' => $meta ?: (object) [],
            'errors' => $errors,
        ], $status, $headers);
    }
}
