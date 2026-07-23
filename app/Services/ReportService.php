<?php

namespace App\Services;

use App\Contract\Repositories\ReportRepositoryInterface;
use App\Models\Household;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReportService
{
    public function __construct(private ReportRepositoryInterface $reportRepository) {}

    public function wasteSummary(): array
    {
        return $this->reportRepository->wasteSummary();
    }

    /** @return array<string, mixed> */
    public function paymentSummary(): array
    {
        return $this->reportRepository->paymentSummary();
    }

    /** @return array<string, mixed> */
    public function householdHistory(string $householdId): array
    {
        $history = $this->reportRepository->householdHistory($householdId);

        if ($history === null) {
            throw (new ModelNotFoundException)->setModel(Household::class, [$householdId]);
        }

        return $history;
    }
}
