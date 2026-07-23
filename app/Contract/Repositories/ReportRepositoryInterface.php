<?php

namespace App\Contract\Repositories;

interface ReportRepositoryInterface
{
    public function wasteSummary(): array;

    public function paymentSummary(): array;

    public function householdHistory(string $householdId): ?array;
}
