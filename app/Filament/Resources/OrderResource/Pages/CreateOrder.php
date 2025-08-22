<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * After the order is created, decrement product stock based on order items.
     */
    protected function afterCreate(): void
    {
        // Ensure we have items and their related products loaded
        $this->record->loadMissing('items.product');

        foreach ($this->record->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $newQty = max(0, (int) $product->quantity - (int) $item->quantity);
            $product->update(['quantity' => $newQty]);
        }
    }
}
