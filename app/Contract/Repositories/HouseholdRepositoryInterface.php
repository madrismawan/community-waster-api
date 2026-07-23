<?php

namespace App\Contract\Repositories;

use App\Models\Household;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** @extends BaseRepositoryInterface<Household> */
interface HouseholdRepositoryInterface extends BaseRepositoryInterface
{
    public function findTrashedOrFail(string $id): Household;

    public function restore(Household $household): Household;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Household>
     */
    public function paginateFiltered(
        int $perPage = 15,
        array $filters = [],
        int $page = 1,
    ): LengthAwarePaginator;
}
