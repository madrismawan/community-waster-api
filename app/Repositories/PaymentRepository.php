<?php

namespace App\Repositories;

use App\Contract\Repositories\PaymentRepositoryInterface;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;

/** @extends BaseRepository<Payment> */
class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $payment)
    {
        parent::__construct($payment);
    }

    public function findByFilters(array $filters): ?Payment
    {
        $query = $this->model->newQuery();
        $allowedFields = $this->model->getFillable();

        foreach ($filters as $key => $value) {
            if (! in_array($key, $allowedFields, true)) {
                throw new InvalidArgumentException(
                    "The field [{$key}] cannot be used to filter payments."
                );
            }

            $query->where($key, $value);
        }

        return $query->first();
    }

    public function save(Payment $payment): Payment
    {
        $payment->save();

        return $payment->refresh();
    }

    public function paginateFiltered(
        int $perPage = 15,
        array $filters = [],
        int $page = 1,
    ): LengthAwarePaginator {
        return $this->applyFilters(Payment::query(), $filters)
            ->latest('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $householdId = trim((string) ($filters['household_id'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if ($householdId !== '') {
            $query->where('household_id', new ObjectId($householdId));
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('payment_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        return $query;
    }
}
