<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Household\IndexHouseholdRequest;
use App\Http\Requests\Household\StoreHouseholdRequest;
use App\Http\Requests\Household\UpdateHouseholdRequest;
use App\Http\Resources\HouseholdResource;
use App\Services\HouseholdService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HouseholdController extends Controller
{
    public function __construct(private HouseholdService $householdService) {}

    public function index(IndexHouseholdRequest $request): JsonResponse
    {
        $paginator = $this->householdService->paginate($request->validated());

        return $this->success(
            data: HouseholdResource::collection($paginator->items()),
            message: 'Households retrieved successfully.',
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

    public function store(StoreHouseholdRequest $request): JsonResponse
    {
        $household = $this->householdService->create($request->validated());

        return $this->success(
            data: new HouseholdResource($household),
            message: 'Household created successfully.',
            status: Response::HTTP_CREATED,
        );
    }

    public function show(string $id): JsonResponse
    {
        return $this->success(
            data: new HouseholdResource($this->householdService->find($id)),
            message: 'Household retrieved successfully.',
        );
    }

    public function update(UpdateHouseholdRequest $request, string $id): JsonResponse
    {
        $updatedHousehold = $this->householdService->update($id, $request->validated());

        return $this->success(
            data: new HouseholdResource($updatedHousehold),
            message: 'Household updated successfully.',
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $this->householdService->delete($id);

        return $this->success(message: 'Household deleted successfully.');
    }
}
