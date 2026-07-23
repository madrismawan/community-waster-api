<?php

namespace App\Models;

use App\Enums\WasteType;

class WastePlastic extends Waste
{
    public static function discriminator(): WasteType
    {
        return WasteType::Plastic;
    }
}
