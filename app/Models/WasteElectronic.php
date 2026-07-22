<?php

namespace App\Models;

use App\Enums\WasteStatus;
use App\Enums\WasteType;
use DomainException;

class WasteElectronic extends Waste
{
    protected static function discriminator(): WasteType
    {
        return WasteType::Electronic;
    }
}
