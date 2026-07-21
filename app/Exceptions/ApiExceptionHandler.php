<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use MongoDB\Driver\Exception\BulkWriteException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

final class ApiExceptionHandler
{
    public static function register(Exceptions $exceptions): void
    {
        $handler = new self;

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $handler->isApiRequest($request),
        );

        $handler->handleValidation($exceptions);
        $handler->handleAuthentication($exceptions);
        $handler->handleAccessDenied($exceptions);
        $handler->handleNotFound($exceptions);
        $handler->handleTooManyRequests($exceptions);
        $handler->handleConflict($exceptions);
        $handler->handleHttpException($exceptions);
        $handler->handleUnexpected($exceptions);
    }

    private function handleValidation(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $this->isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                message: 'The given data was invalid.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                errors: $exception->errors(),
            );
        });
    }

    private function handleAuthentication(Exceptions $exceptions): void
    {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $this->isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                message: $exception->getMessage() ?: 'Unauthenticated.',
                status: Response::HTTP_UNAUTHORIZED,
            );
        });
    }

    private function handleAccessDenied(Exceptions $exceptions): void
    {
        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if (! $this->isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                message: $exception->getMessage() ?: 'This action is unauthorized.',
                status: Response::HTTP_FORBIDDEN,
            );
        });
    }

    private function handleNotFound(Exceptions $exceptions): void
    {
        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $exception, Request $request) {
            if (! $this->isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                message: 'Resource not found.',
                status: Response::HTTP_NOT_FOUND,
            );
        });
    }

    private function handleTooManyRequests(Exceptions $exceptions): void
    {
        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            if (! $this->isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                message: $exception->getMessage() ?: 'Too many requests.',
                status: Response::HTTP_TOO_MANY_REQUESTS,
                headers: $exception->getHeaders(),
            );
        });
    }

    private function handleConflict(Exceptions $exceptions): void
    {
        $exceptions->render(function (ConflictHttpException $exception, Request $request) {
            if (! $this->isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                message: $exception->getMessage() ?: 'The resource conflicts with existing data.',
                status: Response::HTTP_CONFLICT,
                headers: $exception->getHeaders(),
            );
        });
    }

    private function handleHttpException(Exceptions $exceptions): void
    {
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $this->isApiRequest($request)) {
                return null;
            }

            $status = $exception->getStatusCode();

            return ApiResponse::error(
                message: $exception->getMessage() ?: (Response::$statusTexts[$status] ?? 'Request failed.'),
                status: $status,
                headers: $exception->getHeaders(),
            );
        });
    }

    private function handleUnexpected(Exceptions $exceptions): void
    {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $this->isApiRequest($request)) {
                return null;
            }

            report($exception);

            return ApiResponse::error(
                message: 'An unexpected error occurred.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        });
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*');
    }
}
