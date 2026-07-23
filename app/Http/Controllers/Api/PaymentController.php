<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\IndexPaymentRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(IndexPaymentRequest $request): JsonResponse
    {
        $paginator = $this->paymentService->paginate($request->validated());

        return $this->success(
            data: PaymentResource::collection($paginator->items()),
            message: 'Payments retrieved successfully.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        );
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        return $this->success(
            data: new PaymentResource($this->paymentService->create($request->validated())),
            message: 'Payment created successfully.',
            status: Response::HTTP_CREATED,
        );
    }

    public function confirm(string $id): JsonResponse
    {
        return $this->success(
            data: new PaymentResource($this->paymentService->confirm($id)),
            message: 'Payment confirmed successfully.',
        );
    }
}
