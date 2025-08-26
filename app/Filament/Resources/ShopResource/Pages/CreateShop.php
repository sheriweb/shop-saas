<?php

namespace App\Filament\Resources\ShopResource\Pages;

use App\Filament\Resources\ShopResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateShop extends CreateRecord
{
    protected static string $resource = ShopResource::class;

    protected function afterCreate(): void
    {
        $ownerId = $this->form->getState()['owner_user_id'] ?? null;

        if ($ownerId) {
            $user = User::find($ownerId);
            if ($user) {
                $user->update(['shop_id' => $this->record->id]);
                $user->syncRoles(['shop_owner']);
            }
        }
        // Note: The Super Admin should create the shop first, then create users and assign them to the shop
    }
}
