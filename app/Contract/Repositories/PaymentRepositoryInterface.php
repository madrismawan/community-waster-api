<?php

namespace App\Contract\Repositories;

use App\Models\Payment;

/** @extends BaseRepositoryInterface<Payment> */
interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function findByFilters(array $filters): ?Payment;
}
