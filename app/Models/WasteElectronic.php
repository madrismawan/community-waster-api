<?php

namespace App\Models;

use App\Enums\WasteType;

class WasteElectronic extends Waste
{
    protected static function discriminator(): WasteType
    {
        return WasteType::Electronic;
    }
}
