<?php

namespace App\Support;

class PivotSorter
{
    public static function reorder(
        $model,
        string $relationMethod,
        $movedItem,            // int | ['id'=>…] | ['item'=>…, 'position'=>…]
        int $newPosition,
        string $pivotOrderKey,
        array &$localArray,
        ?callable $mapModelToLocal = null
    ): void {
        // Payload robust parsen
        if (is_numeric($movedItem)) {
            $movedItem = ['id' => (int) $movedItem];
        } elseif (is_array($movedItem) && isset($movedItem['id'])) {
            // ok
        } elseif (is_array($movedItem) && array_key_exists('item', $movedItem)) {
            $movedItem = ['id' => (int) $movedItem['item']];
        } else {
            return;
        }

        if (!method_exists($model, $relationMethod)) return;

        // Lokale Liste neu zusammenbauen
        $current  = collect($localArray);
        $filtered = $current->reject(fn($i) => (int)$i['id'] === (int)$movedItem['id'])->values();

        $reordered = collect();
        foreach ($filtered as $index => $item) {
            if ($index == $newPosition) {
                $existing = $current->firstWhere('id', $movedItem['id']) ?? [];
                $reordered->push(array_replace($existing, $movedItem));
            }
            $reordered->push($item);
        }
        if ($newPosition >= $filtered->count()) {
            $existing = $current->firstWhere('id', $movedItem['id']) ?? [];
            $reordered->push(array_replace($existing, $movedItem));
        }

        // Pivot-Sync
        $syncData = $reordered->mapWithKeys(fn($item, $index) => [
            $item['id'] => [$pivotOrderKey => $index]
        ])->toArray();

        $relation = $model->{$relationMethod}();
        $relation->sync($syncData);

        // Lokalen State aus DB neu aufbauen (mit Mapper) + stabil nach Pivot sortieren
        $fresh = $relation->get();

        $localArray = $fresh->map(function ($i) use ($pivotOrderKey, $mapModelToLocal) {
            if ($mapModelToLocal) {
                return $mapModelToLocal($i, $pivotOrderKey);
            }
            // Default (für Entities mit 'name')
            return [
                'id' => $i->id,
                'name' => $i->name ?? null,
                $pivotOrderKey => $i->pivot->{$pivotOrderKey},
            ];
        })
        ->sortBy(fn($row) => $row[$pivotOrderKey])
        ->values()
        ->toArray();
    }
}
