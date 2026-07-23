<?php

namespace App\Repositories;

use App\Contract\Repositories\WasteRepositoryInterface;
use App\Enums\WasteStatus;
use App\Factories\WasteFactory;
use App\Models\Waste;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use MongoDB\BSON\ObjectId;

/** @extends BaseRepository<Waste> */
class WasteRepository extends BaseRepository implements WasteRepositoryInterface
{
    public function __construct(
        Waste $waste,
        private WasteFactory $wasteFactory,
    ) {
        parent::__construct($waste);
    }

    public function create(array $attributes): Waste
    {
        $waste = $this->wasteFactory->make($attributes);
        $waste->save();

        return $waste;
    }

    public function findOrFail(string $id): Waste
    {
        /** @var Waste $waste */
        $waste = parent::findOrFail($id);

        return $this->wasteFactory->hydrate($waste);
    }

    public function save(Waste $waste): Waste
    {
        $waste->save();

        return $waste->refresh();
    }

    public function paginateFiltered(
        int $perPage = 15,
        array $filters = [],
        int $page = 1,
    ): LengthAwarePaginator {
        $paginator = $this->applyFilters(Waste::query(), $filters)
            ->latest('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map($this->wasteFactory->hydrate(...)),
        );

        return $paginator;
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $status = trim((string) ($filters['status'] ?? ''));
        $type = trim((string) ($filters['type'] ?? ''));
        $householdId = trim((string) ($filters['household_id'] ?? ''));

        if ($status !== '' && WasteStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        if ($type !== '' && WasteType::tryFrom($type) !== null) {
            $query->where('type', $type);
        }

        if ($householdId !== '') {
            $query->where('household_id', new ObjectId($householdId));
        }

        return $query;
    }
}
