<?php

namespace App\Models;

use App\Enums\WasteType;
use DomainException;

class WasteElectronic extends Waste
{
    public static function discriminator(): WasteType
    {
        return WasteType::Electronic;
    }

    public function schedule(string $pickupDate, ?bool $safetyCheck = null): void
    {
        if ($safetyCheck !== true) {
            throw new DomainException('Electronic waste requires a safety check before scheduling.');
        }

        parent::schedule($pickupDate, $safetyCheck);
    }
}
