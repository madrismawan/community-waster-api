<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Household\IndexHouseholdRequest;
use App\Http\Requests\Household\StoreHouseholdRequest;
use App\Http\Requests\Household\UpdateHouseholdRequest;
use App\Http\Resources\HouseholdResource;
use App\Services\HouseholdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HouseholdController extends Controller
{
    public function __construct(private HouseholdService $households) {}

    public function index(IndexHouseholdRequest $request): JsonResponse
    {
        $perPage = (int) $request->validated('per_page', 15);

        return $this->success(
            HouseholdResource::collection($this->households->paginate($perPage)),
            'Households retrieved successfully.',
        );
    }

    public function store(StoreHouseholdRequest $request): JsonResponse
    {
        $household = $this->households->create($request->validated());

        return $this->success(
            (new HouseholdResource($household))->resolve($request),
            'Household created successfully.',
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, string $household): JsonResponse
    {
        return $this->success(
            (new HouseholdResource($this->households->find($household)))->resolve($request),
            'Household retrieved successfully.',
        );
    }

    public function update(UpdateHouseholdRequest $request, string $household): JsonResponse
    {
        $updatedHousehold = $this->households->update($household, $request->validated());

        return $this->success(
            (new HouseholdResource($updatedHousehold))->resolve($request),
            'Household updated successfully.',
        );
    }

    public function destroy(string $household): JsonResponse
    {
        $this->households->delete($household);

        return $this->success(message: 'Household deleted successfully.');
    }
}
