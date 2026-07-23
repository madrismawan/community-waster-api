<?php

namespace App\Contract\Repositories;

use App\Models\Waste;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** @extends BaseRepositoryInterface<Waste> */
interface WasteRepositoryInterface extends BaseRepositoryInterface
{
    public function save(Waste $waste): Waste;

    public function cancelOverdueOrganicPickups(DateTimeInterface $cutoff): int;

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
