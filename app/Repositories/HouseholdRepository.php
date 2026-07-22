<?php

namespace App\Repositories;

use App\Contract\Repositories\HouseholdRepositoryInterface;
use App\Models\Household;

class HouseholdRepository extends BaseRepository implements HouseholdRepositoryInterface
{
    public function __construct(Household $household)
    {
        parent::__construct($household);
    }

    public function locationExists(string $block, string $number, ?string $exceptId = null): bool
    {
        $query = Household::query()
            ->where('block', $block)
            ->where('no', $number);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        return $query->exists();
    }
}
