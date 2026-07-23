<?php

namespace App\Models;

use App\Enums\WasteType;

class WasteOrganic extends Waste
{
    public const int AUTO_CANCEL_AFTER_DAYS = 3;

    public const string AUTO_CANCEL_TIME = '01:00';

    public const string AUTO_CANCEL_TIMEZONE = 'Asia/Makassar';

    protected string $paymentAmount = '50000.00';

    public static function discriminator(): WasteType
    {
        return WasteType::Organic;
    }
}
