<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Shop selection - visible only to Super Admin, otherwise auto-assigned in CreateCategory page
                Forms\Components\Select::make('shop_id')
                    ->relationship('shop', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn () => Auth::check() && in_array('super_admin', Auth::user()?->roles->pluck('name')->toArray() ?? [])),
                Forms\Components\TextInput::make('name_en')
                    ->label('Name (English)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_ur')
                    ->label('Name (Urdu)')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name_en')->searchable(),
                Tables\Columns\TextColumn::make('name_ur')->searchable(),
            ])
            ->filters([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (!Auth::check()) return false;
        
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Super admin can always view
        if (in_array('super_admin', $userRoles)) {
            return true;
        }
        
        // Shop owners and staff need an active subscription to view
        if ((in_array('shop_owner', $userRoles) || in_array('staff', $userRoles)) && $user->shop) {
            // Explicitly check for deactivated subscriptions
            $hasDeactivatedSubscription = $user->shop->subscriptions()
                ->where('status', 'deactivated')
                ->whereDate('end_date', '>=', now()->toDateString())
                ->exists();
                
            if ($hasDeactivatedSubscription) {
                return false;
            }
            
            return $user->shop->hasActiveSubscription();
        }
        
        return false;
    }
    
    public static function canCreate(): bool
    {
        // Check if user exists
        if (!Auth::check()) return false;
        
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Super admin can always create
        if (in_array('super_admin', $userRoles)) {
            return true;
        }
        
        // Shop owners need an active subscription to create
        if (in_array('shop_owner', $userRoles) || in_array('staff', $userRoles)) {
            // Check if shop can perform operations
            return $user->shop && $user->shop->canPerformOperations();
        }
        
        return false;
    }
    
    public static function canEdit(Model $record): bool
    {
        // Check if user exists
        if (!Auth::check()) return false;
        
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Super admin can always edit
        if (in_array('super_admin', $userRoles)) {
            return true;
        }
        
        // Shop owners need an active subscription to edit
        if ((in_array('shop_owner', $userRoles) || in_array('staff', $userRoles)) && $record->shop_id === $user->shop_id) {
            // Check if shop can perform operations
            return $user->shop && $user->shop->canPerformOperations();
        }
        
        return false;
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (!Auth::check()) return $query;
        
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Super Admins can see all categories
        if (in_array('super_admin', $userRoles)) {
            return $query;
        }
        
        // Shop Owners can only see categories from their shop
        if (in_array('shop_owner', $userRoles) && $user->shop_id) {
            return $query->where('shop_id', $user->shop_id);
        }
        
        return $query;
    }
}
