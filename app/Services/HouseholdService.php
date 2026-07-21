<?php

namespace App\Services;

use App\Contract\Repositories\HouseholdRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class HouseholdService
{
    public function __construct(private HouseholdRepositoryInterface $households) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->households->paginate($perPage);
    }

    public function find(string $id): Model
    {
        return $this->households->findOrFail($id);
    }

    public function create(array $attributes): Model
    {
        $attributes = $this->normalize($attributes);
        $this->ensureLocationIsAvailable(
            $attributes['block'] ?? null,
            $attributes['no'] ?? null,
        );

        return $this->households->create($attributes);
    }

    public function update(string $id, array $attributes): Model
    {
        $household = $this->find($id);
        $attributes = $this->normalize($attributes);
        $block = array_key_exists('block', $attributes)
            ? $attributes['block']
            : $household->getAttribute('block');
        $number = array_key_exists('no', $attributes)
            ? $attributes['no']
            : $household->getAttribute('no');

        $this->ensureLocationIsAvailable($block, $number, (string) $household->getKey());

        return $this->households->update($household, $attributes);
    }

    public function delete(string $id): void
    {
        $this->households->delete($this->find($id));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalize(array $attributes): array
    {
        foreach (['owner_name', 'address', 'block', 'no'] as $field) {
            if (array_key_exists($field, $attributes) && is_string($attributes[$field])) {
                $attributes[$field] = trim($attributes[$field]);
            }
        }

        if (isset($attributes['block'])) {
            $attributes['block'] = mb_strtoupper($attributes['block']);
        }

        return $attributes;
    }

    private function ensureLocationIsAvailable(?string $block, ?string $number, ?string $exceptId = null): void
    {
        if ($block === null || $number === null) {
            return;
        }

        if ($this->households->locationExists($block, $number, $exceptId)) {
            throw new ConflictHttpException(
                'A household with the same block and house number already exists.',
            );
        }
    }
}
