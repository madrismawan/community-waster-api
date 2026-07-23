<?php

namespace App\Models;

use App\Enums\WasteType;

class WastePaper extends Waste
{
    public static function discriminator(): WasteType
    {
        return WasteType::Paper;
    }
}
