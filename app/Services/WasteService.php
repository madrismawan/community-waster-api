<?php

namespace App\Services;

use App\Contract\Repositories\PaymentRepositoryInterface;
use App\Contract\Repositories\WasteRepositoryInterface;
use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Models\Waste;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use MongoDB\BSON\ObjectId;

class WasteService
{
    public function __construct(
        private WasteRepositoryInterface $wasteRepository,
        private PaymentRepositoryInterface $paymentRepository,
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

        $unpaidPayment = $this->paymentRepository->findByFilters([
            'household_id' => $request['household_id'] ? new ObjectId($request['household_id']) : null,
            'status' => PaymentStatus::Pending->value,
        ]);

        if ($unpaidPayment !== null) {
            throw new DomainException(
                'Cannot create a waste pickup because the household has an unpaid payment.'
            );
        }

        $payload = Arr::only($request, (new Waste)->getFillable());
        $payload['pickup_date'] = null;
        $payload['status'] = WasteStatus::Pending;

        return $this->wasteRepository->create($payload);
    }

    public function schedule(string $id, array $request): Waste
    {
        $waste = $this->find($id);
        $safetyCheck = $request['safety_check'] ?? false;

        $waste->schedule($request['pickup_date'], $safetyCheck);

        return $this->wasteRepository->save($waste);
    }

    public function complete(string $id): Waste
    {
        $waste = $this->find($id);

        $waste->complete();

        return $this->wasteRepository->save($waste);
    }

    public function cancel(string $id): Waste
    {
        $waste = $this->find($id);

        $waste->cancel();

        return $this->wasteRepository->save($waste);
    }
}
