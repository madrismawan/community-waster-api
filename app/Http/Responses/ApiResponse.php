<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponse
{
    public static function success(
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

    /**
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>|null  $meta
     */
    public static function error(
        string $message,
        int $status,
        ?array $errors = null,
        array $headers = [],
        ?array $meta = null,
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => (object) [],
            'meta' => $meta ?: (object) [],
            'errors' => $errors,
        ];

        return response()->json($response, $status, $headers);
    }
}
