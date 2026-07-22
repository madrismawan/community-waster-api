<?php

namespace App\Repositories;

use App\Contract\Repositories\HouseholdRepositoryInterface;
use App\Models\Household;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use MongoDB\BSON\Regex;

/**
 * @extends BaseRepository<Household>
 */
class HouseholdRepository extends BaseRepository implements HouseholdRepositoryInterface
{
    public function __construct(Household $household)
    {
        parent::__construct($household);
    }

    /** @return LengthAwarePaginator<int, Household> */
    public function paginateFiltered(
        int $perPage = 15,
        array $filters = [],
        int $page = 1,
    ): LengthAwarePaginator {
        return $this->applyFilters(Household::query(), $filters)
            ->latest('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param  Builder<Household>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Household>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $block = trim((string) ($filters['block'] ?? ''));
        $number = trim((string) ($filters['no'] ?? ''));

        if ($search !== '') {
            $pattern = new Regex(preg_quote($search), 'i');

            $query->where(function (Builder $query) use ($pattern): void {
                $query->where('owner_name', 'regex', $pattern)
                    ->orWhere('address', 'regex', $pattern);
            });
        }

        if ($block !== '') {
            $query->where('block', mb_strtoupper($block));
        }

        if ($number !== '') {
            $query->where('no', $number);
        }

        return $query;
    }
}
