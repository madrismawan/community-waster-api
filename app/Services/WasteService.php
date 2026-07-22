<?php

namespace App\Services;

use App\Contract\Repositories\HouseholdRepositoryInterface;
use App\Contract\Repositories\WasteRepositoryInterface;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Models\Waste;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class WasteService
{
    public function __construct(
        private WasteRepositoryInterface $wasteRepository,
        private HouseholdRepositoryInterface $householdRepository,
    ) {}

    public function paginate(array $request = []): LengthAwarePaginator
    {

        $perPage = (int) ($request['per_page'] ?? 15);
        $page = (int) ($request['page'] ?? 1);

        return $this->wasteRepository->paginateFiltered($perPage, $request, $page);
    }

    public function find(string $id): Waste
    {
        return $this->wasteRepository->findOrFail($id);
    }

    public function create(array $request): Waste
    {
        $this->householdRepository->findOrFail($request['household_id']);

        $payload = Arr::only($request, (new Waste)->getFillable());
        $payload['pickup_date'] = null;
        $payload['status'] = WasteStatus::Pending;

        return $this->wasteRepository->create($payload);
    }

    public function schedule(string $id, array $request): Waste
    {
        $waste = $this->find($id);

        $this->ensureStatus($waste, [WasteStatus::Pending], 'Only pending pickups can be scheduled.');

        $safetyCheck = (bool) ($request['safety_check'] ?? $waste->safety_check);

        if ($waste->type === WasteType::Electronic && ! $safetyCheck) {
            throw new DomainException('Electronic waste requires a safety check before scheduling.');
        }

        $payload = [
            'pickup_date' => $request['pickup_date'],
            'status' => WasteStatus::Scheduled,
        ];

        if (array_key_exists('safety_check', $request)) {
            $payload['safety_check'] = $safetyCheck;
        }

        return $this->wasteRepository->update($waste, $payload);
    }

    public function complete(string $id): Waste
    {
        $waste = $this->find($id);

        $this->ensureStatus($waste, [WasteStatus::Scheduled], 'Only scheduled pickups can be completed.');

        return $this->wasteRepository->update($waste, ['status' => WasteStatus::Completed]);
    }

    public function cancel(string $id): Waste
    {
        $waste = $this->find($id);

        $this->ensureStatus(
            $waste,
            [WasteStatus::Pending, WasteStatus::Scheduled],
            'Only pending or scheduled pickups can be canceled.',
        );

        return $this->wasteRepository->update($waste, ['status' => WasteStatus::Canceled]);
    }

    /**
     * @param  list<WasteStatus>  $allowedStatuses
     */
    private function ensureStatus(Waste $waste, array $allowedStatuses, string $message): void
    {
        if (! in_array($waste->status, $allowedStatuses, true)) {
            throw new DomainException($message);
        }
    }
}
