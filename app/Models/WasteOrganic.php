<?php

namespace App\Models;

use App\Enums\WasteType;

class WasteOrganic extends Waste
{
    protected string $paymentAmount = '50000.00';

    public static function discriminator(): WasteType
    {
        return WasteType::Organic;
    }
}
