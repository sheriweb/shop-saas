<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If user is not a super_admin, set shop_id to their shop
        if (Auth::check()) {
            $user = Auth::user();
            $userRoles = $user->roles->pluck('name')->toArray();
            
            if (!in_array('super_admin', $userRoles) && $user->shop_id) {
                $data['shop_id'] = $user->shop_id;
            }
        }
        
        return $data;
    }
}
