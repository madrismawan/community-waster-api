<?php

namespace App\Contract\Repositories;

interface HouseholdRepositoryInterface extends BaseRepositoryInterface
{
    public function locationExists(string $block, string $number, ?string $exceptId = null): bool;
}
