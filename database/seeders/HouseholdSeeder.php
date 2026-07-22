<?php

namespace Database\Seeders;

use App\Models\Household;
use Illuminate\Database\Seeder;

class HouseholdSeeder extends Seeder
{
    public function run(): void
    {
        $households = [
            ['owner_name' => 'Ayu Lestari', 'address' => 'Jalan Melati No. 1', 'block' => 'A', 'no' => '01'],
            ['owner_name' => 'Budi Santoso', 'address' => 'Jalan Melati No. 2', 'block' => 'A', 'no' => '02'],
            ['owner_name' => 'Citra Dewi', 'address' => 'Jalan Kenanga No. 3', 'block' => 'B', 'no' => '01'],
            ['owner_name' => 'Dedi Kurniawan', 'address' => 'Jalan Kenanga No. 4', 'block' => 'B', 'no' => '02'],
            ['owner_name' => 'Eka Putri', 'address' => 'Jalan Mawar No. 5', 'block' => 'C', 'no' => '01'],
            ['owner_name' => 'Fajar Hidayat', 'address' => 'Jalan Mawar No. 6', 'block' => 'C', 'no' => '02'],
            ['owner_name' => 'Gita Permata', 'address' => 'Jalan Anggrek No. 7', 'block' => 'D', 'no' => '01'],
            ['owner_name' => 'Hendra Wijaya', 'address' => 'Jalan Anggrek No. 8', 'block' => 'D', 'no' => '02'],
            ['owner_name' => 'Indah Sari', 'address' => 'Jalan Flamboyan No. 9', 'block' => 'E', 'no' => '01'],
            ['owner_name' => 'Joko Susilo', 'address' => 'Jalan Flamboyan No. 10', 'block' => 'E', 'no' => '02'],
        ];

        foreach ($households as $household) {
            Household::query()->create($household);
        }
    }
}
