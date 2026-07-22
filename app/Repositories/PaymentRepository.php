<?php

namespace App\Repositories;

use App\Contract\Repositories\PaymentRepositoryInterface;
use App\Models\Payment;
use InvalidArgumentException;

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
}
