<?php

namespace Database\Seeders;

use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Models\Household;
use App\Models\Waste;
use Illuminate\Database\Seeder;
use RuntimeException;

class WasteSeeder extends Seeder
{
    public function run(): void
    {
        $households = Household::query()->get();

        if ($households->count() < 1) {
            throw new RuntimeException('At least one household is required to seed wastes.');
        }

        $households = $households->random(1)->values();

        $wastes = [
            [
                'type' => WasteType::Organic,
                'status' => WasteStatus::Pending,
                'pickup_date' => null,
            ],
            [
                'type' => WasteType::Plastic,
                'status' => WasteStatus::Scheduled,
                'pickup_date' => now()->addDay(),
            ],
            [
                'type' => WasteType::Paper,
                'status' => WasteStatus::Completed,
                'pickup_date' => now()->subDay(),
            ],
            [
                'type' => WasteType::Electronic,
                'status' => WasteStatus::Scheduled,
                'pickup_date' => now()->addDays(2),
                'safety_check' => true,
            ],
            [
                'type' => WasteType::Organic,
                'status' => WasteStatus::Canceled,
                'pickup_date' => null,
            ],
        ];

        foreach ($households as $index => $household) {
            $attributes = $wastes[$index];
            $type = $attributes['type'];

            unset($attributes['type']);

            $waste = new Waste($attributes);
            $waste->household()->associate($household);
            $waste->type = $type;
            $waste->save();
        }
    }
}
