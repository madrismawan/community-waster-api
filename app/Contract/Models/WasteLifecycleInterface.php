<?php

namespace App\Contract\Models;

interface WasteLifecycleInterface
{
    public function schedule(string $pickupDate, ?bool $safetyCheck = null): void;

    public function complete(): void;

    public function cancel(): void;
}
