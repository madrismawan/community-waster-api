<?php

namespace App\Models;

use App\Enums\WasteType;

class WastePlastic extends Waste
{
    protected static function discriminator(): WasteType
    {
        return WasteType::Plastic;
    }
}
