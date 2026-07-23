<?php

namespace App\Services;

use App\Contract\Repositories\PaymentRepositoryInterface;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use MongoDB\BSON\ObjectId;

class PaymentService
{
    public function __construct(private PaymentRepositoryInterface $paymentRepository) {}

    public function paginate(array $request = []): LengthAwarePaginator
    {
        $perPage = (int) ($request['per_page'] ?? 15);
        $page = (int) ($request['page'] ?? 1);

        return $this->paymentRepository->paginateFiltered($perPage, $request, $page);
    }

    public function find(string $id): Payment
    {
        return $this->paymentRepository->findOrFail($id);
    }

    public function create(array $request): Payment
    {
        $payload = Arr::only($request, (new Payment)->getFillable());
        $payload['household_id'] = new ObjectId($request['household_id']);
        $payload['payment_date'] = now()->addWeek();
        $payload['status'] = PaymentStatus::Pending;

        return $this->paymentRepository->create($payload);
    }

    public function confirm(string $id): Payment
    {
        $payment = $this->find($id);
        if ($payment->status !== PaymentStatus::Pending) {
            throw new DomainException('Only pending payments can be confirmed.');
        }
        $payment->status = PaymentStatus::Paid;
        return $this->paymentRepository->save($payment);
    }
}
