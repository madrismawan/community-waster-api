<?php

namespace App\Contract\Repositories;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** @extends BaseRepositoryInterface<Payment> */
interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function findByFilters(array $filters): ?Payment;

    public function save(Payment $payment): Payment;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginateFiltered(
        int $perPage = 15,
        array $filters = [],
        int $page = 1,
    ): LengthAwarePaginator;
}
