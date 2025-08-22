<?php

namespace App\Filament\Resources\ShopResource\Pages;

use App\Filament\Resources\ShopResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShop extends EditRecord
{
    protected static string $resource = ShopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Prefill owner_user_id with current owner (if any)
        $currentOwnerId = User::query()
            ->where('shop_id', $this->record->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'shop_owner'))
            ->value('id');

        if ($currentOwnerId) {
            $data['owner_user_id'] = $currentOwnerId;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $state = $this->form->getState();
        $newOwnerId = $state['owner_user_id'] ?? null;

        // Find current owner (if any)
        $currentOwner = User::query()
            ->where('shop_id', $this->record->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'shop_owner'))
            ->first();

        if ($currentOwner && ($newOwnerId === null || $currentOwner->id !== (int) $newOwnerId)) {
            // Detach previous owner from this shop
            $currentOwner->update(['shop_id' => null]);
            // Optionally change role back to staff if desired
            // $currentOwner->syncRoles(['staff']);
        }

        if ($newOwnerId) {
            $newOwner = User::find($newOwnerId);
            if ($newOwner) {
                $newOwner->update(['shop_id' => $this->record->id]);
                $newOwner->syncRoles(['shop_owner']);
            }
        }
    }
}
