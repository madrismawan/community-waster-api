<?php

namespace App\Contract\Repositories;

use App\Models\Household;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** @extends BaseRepositoryInterface<Household> */
interface HouseholdRepositoryInterface extends BaseRepositoryInterface
{
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
