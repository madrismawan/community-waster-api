<?php

namespace App\Contract\Repositories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

/** @extends BaseRepositoryInterface<Payment> */
interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, Payment>
     */
    public function getByFilter(string $key, mixed $value): Collection;
}
