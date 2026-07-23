<?php

namespace App\Enums;

use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;

enum WasteType: string
{
    case Organic = 'organic';
    case Plastic = 'plastic';
    case Paper = 'paper';
    case Electronic = 'electronic';

    /** @return class-string<Waste> */
    public function modelClass(): string
    {
        return match ($this) {
            self::Organic => WasteOrganic::class,
            self::Plastic => WastePlastic::class,
            self::Paper => WastePaper::class,
            self::Electronic => WasteElectronic::class,
        };
    }
}
