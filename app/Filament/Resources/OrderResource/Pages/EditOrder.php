<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Keep snapshot of original items to reconcile stock on update.
     * @var array<int, array{product_id:int, quantity:int}>
     */
    protected array $originalItems = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Snapshot original items
        $this->record->loadMissing('items');
        $this->originalItems = $this->record->items
            ->map(fn ($i) => [
                'product_id' => (int) $i->product_id,
                'quantity'   => (int) $i->quantity,
            ])->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    // Restock all current items before deletion
                    $record->loadMissing('items.product');
                    foreach ($record->items as $item) {
                        if ($item->product) {
                            $item->product->increment('quantity', (int) $item->quantity);
                        }
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        // Restock original items first
        foreach ($this->originalItems as $orig) {
            $productId = $orig['product_id'] ?? null;
            $qty       = (int) ($orig['quantity'] ?? 0);
            if ($productId && $qty > 0) {
                // increment back
                \App\Models\Product::whereKey($productId)->increment('quantity', $qty);
            }
        }

        // Deduct new items
        $this->record->loadMissing('items.product');
        foreach ($this->record->items as $item) {
            if ($item->product) {
                $newQty = max(0, (int) $item->product->quantity - (int) $item->quantity);
                $item->product->update(['quantity' => $newQty]);
            }
        }
    }
}
