<?php

namespace App\Services;

use App\Contract\Repositories\HouseholdRepositoryInterface;
use App\Models\Household;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class HouseholdService
{
    public function __construct(private HouseholdRepositoryInterface $householdRepo) {}

    public function paginate(array $request = []): LengthAwarePaginator
    {
        $perPage = (int) ($request['per_page'] ?? 15);
        $page = (int) ($request['page'] ?? 1);

        return $this->householdRepo->paginateFiltered($perPage, $request, $page);
    }

    public function find(string $id): Household
    {
        return $this->householdRepo->findOrFail($id);
    }

    public function create(array $request): Household
    {
        $payload = $this->payload($request);

        return $this->householdRepo->create($payload);
    }

    public function update(string $id, array $request): Household
    {
        $household = $this->find($id);
        $payload = $this->payload($request);

        return $this->householdRepo->update($household, $payload);
    }

    public function delete(string $id): void
    {
        $this->householdRepo->delete($this->find($id));
    }

    private function payload(array $request): array
    {
        return Arr::only($request, (new Household)->getFillable());
    }
}
