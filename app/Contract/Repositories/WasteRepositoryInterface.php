<?php

namespace App\Contract\Repositories;

use App\Models\Waste;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** @extends BaseRepositoryInterface<Waste> */
interface WasteRepositoryInterface extends BaseRepositoryInterface
{
    public function save(Waste $waste): Waste;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Waste>
     */
    public function paginateFiltered(
        int $perPage = 15,
        array $filters = [],
        int $page = 1,
    ): LengthAwarePaginator;
}
