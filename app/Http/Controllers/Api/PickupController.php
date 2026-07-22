<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pickup\IndexPickupRequest;
use App\Http\Requests\Pickup\SchedulePickupRequest;
use App\Http\Requests\Pickup\StorePickupRequest;
use App\Http\Resources\WasteResource;
use App\Services\WasteService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PickupController extends Controller
{
    public function __construct(private WasteService $wasteService) {}

    public function index(IndexPickupRequest $request): JsonResponse
    {
        $paginator = $this->wasteService->paginate($request->validated());

        return $this->success(
            data: WasteResource::collection($paginator->items()),
            message: 'Pickups retrieved successfully.',
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

    public function store(StorePickupRequest $request): JsonResponse
    {
        return $this->success(
            data: new WasteResource($this->wasteService->create($request->validated())),
            message: 'Pickup created successfully.',
            status: Response::HTTP_CREATED,
        );
    }

    public function schedule(SchedulePickupRequest $request, string $id): JsonResponse
    {
        return $this->success(
            data: new WasteResource($this->wasteService->schedule($id, $request->validated())),
            message: 'Pickup scheduled successfully.',
        );
    }

    public function complete(string $id): JsonResponse
    {
        return $this->success(
            data: new WasteResource($this->wasteService->complete($id)),
            message: 'Pickup completed successfully.',
        );
    }

    public function cancel(string $id): JsonResponse
    {
        return $this->success(
            data: new WasteResource($this->wasteService->cancel($id)),
            message: 'Pickup canceled successfully.',
        );
    }
}
