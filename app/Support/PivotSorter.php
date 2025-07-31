<?php

namespace App\Support;

class PivotSorter
{
    /**
     * Reorders a pivot relation based on the given new order.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $relationMethod
     * @param array $movedItem ['id' => int]
     * @param int $newPosition
     * @param string $pivotOrderKey
     * @param array &$localArray Reference to local array to update
     * @return void
     */
    public static function reorder(
        $model,
        string $relationMethod,
        array $movedItem,
        int $newPosition,
        string $pivotOrderKey,
        array &$localArray
    ): void {
        if (!isset($movedItem['id'])) {
            return;
        }

        if (!method_exists($model, $relationMethod)) {
            return;
        }

        // Liste sortieren
        $current = collect($localArray);
        $filtered = $current->reject(fn($i) => $i['id'] == $movedItem['id'])->values();

        $reordered = collect();
        foreach ($filtered as $index => $item) {
            if ($index == $newPosition) {
                $reordered->push($movedItem);
            }
            $reordered->push($item);
        }
        if ($newPosition >= $filtered->count()) {
            $reordered->push($movedItem);
        }

        // Pivot-Sync-Daten vorbereiten
        $syncData = $reordered->mapWithKeys(fn($item, $index) => [
            $item['id'] => [$pivotOrderKey => $index]
        ])->toArray();

        // Sync durchführen
        $model->{$relationMethod}()->sync($syncData);

        // Lokale Liste aktualisieren
        $localArray = $model->{$relationMethod}()
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->name,
                $pivotOrderKey => $i->pivot->{$pivotOrderKey}
            ])
            ->values()
            ->toArray();
    }
}
