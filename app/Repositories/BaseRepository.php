<?php

namespace App\Repositories;

use App\Contract\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 * @implements BaseRepositoryInterface<TModel>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /** @var TModel */
    protected Model $model;

    /** @param TModel $model */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /** @return LengthAwarePaginator<int, TModel> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->latest('created_at')
            ->paginate($perPage);
    }

    /** @return TModel */
    public function findOrFail(string $id): Model
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes)->save();

        return $model->refresh();
    }

    /** @param TModel $model */
    public function delete(Model $model): void
    {
        $model->delete();
    }
}
