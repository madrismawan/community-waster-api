<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HouseholdHistoryResource;
use App\Http\Resources\PaymentSummaryResource;
use App\Http\Resources\WasteSummaryResource;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function wasteSummary(): JsonResponse
    {
        return $this->success(
            data: WasteSummaryResource::collection($this->reportService->wasteSummary()),
            message: 'Waste summary retrieved successfully.',
        );
    }

    public function paymentSummary(): JsonResponse
    {
        return $this->success(
            data: new PaymentSummaryResource($this->reportService->paymentSummary()),
            message: 'Payment summary retrieved successfully.',
        );
    }

    public function householdHistory(string $id): JsonResponse
    {
        return $this->success(
            data: new HouseholdHistoryResource($this->reportService->householdHistory($id)),
            message: 'Household history retrieved successfully.',
        );
    }
}
