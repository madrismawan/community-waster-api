<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    public function success(
        mixed $data = null,
        string $message = 'Request completed successfully.',
        int $status = Response::HTTP_OK,
        ?array $meta = null,
    ): JsonResponse {
        if ($data instanceof AnonymousResourceCollection) {
            $resource = $data->response()->getData(true);
            $data = $resource['data'] ?? [];
            $meta = array_merge($resource['meta'] ?? [], $meta ?? []);

            if (isset($resource['links'])) {
                $meta['navigation'] = $resource['links'];
            }
        }

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
