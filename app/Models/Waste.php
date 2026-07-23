<?php

namespace App\Models;

use App\Contract\Models\WasteLifecycleInterface;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Casts\ObjectId;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['household_id', 'type', 'pickup_date', 'status'])]
class Waste extends Model implements WasteLifecycleInterface
{
    protected $connection = 'mongodb';

    protected $table = 'wastes';

    protected string $paymentAmount;

    protected function casts(): array
    {
        return [
            'household_id' => ObjectId::class,
            'type' => WasteType::class,
            'pickup_date' => 'datetime',
            'status' => WasteStatus::class,
            'safety_check' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public static function discriminator(): ?WasteType
    {
        return null;
    }

    public function paymentAmount(): string
    {
        return $this->paymentAmount;
    }

    public function schedule(string $pickupDate, ?bool $safetyCheck = null): void
    {
        if ($this->status !== WasteStatus::Pending) {
            throw new DomainException('Only pending pickups can be scheduled.');
        }

        $this->pickup_date = $pickupDate ?? null;
        $this->status = WasteStatus::Scheduled;
    }

    public function complete(): void
    {
        if ($this->status !== WasteStatus::Scheduled) {
            throw new DomainException('Only scheduled pickups can be completed.');
        }

        $this->status = WasteStatus::Completed;
    }

    public function cancel(): void
    {
        if (! in_array($this->status, [WasteStatus::Pending, WasteStatus::Scheduled], true)) {
            throw new DomainException('Only pending or scheduled pickups can be canceled.');
        }

        $this->status = WasteStatus::Canceled;
    }
}
