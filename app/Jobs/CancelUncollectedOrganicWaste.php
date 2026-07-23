<?php

namespace App\Jobs;

use App\Contract\Repositories\WasteRepositoryInterface;
use App\Models\WasteOrganic;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CancelUncollectedOrganicWaste implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onConnection('mongodb');
    }

    public function handle(WasteRepositoryInterface $wasteRepository): void
    {
        $cutoff = now()
            ->setTimezone(WasteOrganic::AUTO_CANCEL_TIMEZONE)
            ->subDays(WasteOrganic::AUTO_CANCEL_AFTER_DAYS)
            ->endOfDay()
            ->utc();

        $wasteRepository->cancelOverdueOrganicPickups($cutoff);
    }
}
