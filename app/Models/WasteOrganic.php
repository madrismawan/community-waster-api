<?php

namespace App\Models;

use App\Enums\WasteStatus;
use App\Enums\WasteType;

class WasteOrganic extends Waste
{
    protected static function discriminator(): WasteType
    {
        return WasteType::Organic;
    }
}
