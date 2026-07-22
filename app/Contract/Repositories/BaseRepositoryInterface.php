<?php

namespace App\Contract\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/** @template TModel of Model */
interface BaseRepositoryInterface
{
    /** @return LengthAwarePaginator<int, TModel> */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /** @return TModel */
    public function findOrFail(string $id): Model;

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model;

    /** @param TModel $model */
    public function delete(Model $model): void;
}
