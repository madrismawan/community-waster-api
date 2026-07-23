<?php

namespace App\Factories;

use App\Enums\WasteType;
use App\Models\Waste;

class WasteFactory
{
    /** @param array<string, mixed> $attributes */
    public function make(array $attributes): Waste
    {
        $type = $attributes['type'] instanceof WasteType
            ? $attributes['type']
            : WasteType::from($attributes['type']);
        $modelClass = $type->modelClass();
        $attributes['type'] = $type;

        return new $modelClass($attributes);
    }

    public function hydrate(Waste $waste): Waste
    {
        if ($waste::class !== Waste::class) {
            return $waste;
        }

        $modelClass = $waste->type->modelClass();
        $resolved = (new $modelClass)->newFromBuilder(
            $waste->getAttributes(),
            $waste->getConnectionName(),
        );

        $resolved->setRelations($waste->getRelations());

        return $resolved;
    }
}
