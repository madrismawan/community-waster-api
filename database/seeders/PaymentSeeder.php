<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\Household;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use RuntimeException;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $households = Household::query()->get();

        if ($households->count() < 1) {
            throw new RuntimeException('At least one household is required to seed payments.');
        }

        $households = $households->random(5)->values();

        $payments = [
            [
                'amount' => '50000.00',
                'status' => PaymentStatus::Paid,
                'days_ago' => 5,
            ],
            [
                'amount' => '75000.00',
                'status' => PaymentStatus::Pending,
                'days_ago' => 4,
            ],
            [
                'amount' => '60000.00',
                'status' => PaymentStatus::Failed,
                'days_ago' => 3,
            ],
            [
                'amount' => '85000.00',
                'status' => PaymentStatus::Paid,
                'days_ago' => 2,
            ],
            [
                'amount' => '100000.00',
                'status' => PaymentStatus::Pending,
                'days_ago' => 1,
            ],
        ];

        foreach ($households as $index => $household) {
            $attributes = $payments[$index];

            $payment = new Payment([
                'amount' => $attributes['amount'],
                'payment_date' => now()->subDays($attributes['days_ago']),
                'status' => $attributes['status'],
            ]);
            $payment->household()->associate($household);
            $payment->save();
        }
    }
}
