<?php

namespace App\Contract\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(string $id): Model;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Model;

    /** @param array<string, mixed> $attributes */
    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): void;
}
