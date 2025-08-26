<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
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

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Shop selection - visible only to Super Admin, otherwise auto-assigned in CreateProduct page
                Forms\Components\Select::make('shop_id')
                    ->relationship('shop', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn () => Auth::check() && in_array('super_admin', Auth::user()?->roles->pluck('name')->toArray() ?? [])),
                Forms\Components\Select::make('category_id')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name_en',
                        modifyQueryUsing: function ($query) {
                            if (Auth::check() && ! in_array('super_admin', Auth::user()?->roles->pluck('name')->toArray() ?? [])) {
                                $query->where('shop_id', Auth::user()->shop_id);
                            }
                        }
                    )
                    ->required(),
                Forms\Components\TextInput::make('sku')->unique()->required(),
                Forms\Components\TextInput::make('barcode'),
                Forms\Components\TextInput::make('name_en')->label('Name (English)')->required(),
                Forms\Components\TextInput::make('name_ur')->label('Name (Urdu)'),
                Forms\Components\Textarea::make('description_en'),
                Forms\Components\Textarea::make('description_ur'),
                Forms\Components\TextInput::make('cost_price')->numeric()->required(),
                Forms\Components\TextInput::make('sale_price')->numeric()->required(),
                Forms\Components\TextInput::make('quantity')->numeric()->default(0),
                Forms\Components\TextInput::make('low_stock_threshold')->numeric()->default(5),
                Forms\Components\Toggle::make('status')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.name'),
                Tables\Columns\TextColumn::make('category.name_en'),
                Tables\Columns\TextColumn::make('sku'),
                Tables\Columns\TextColumn::make('name_en'),
                Tables\Columns\TextColumn::make('sale_price')->money('PKR'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\IconColumn::make('status')->boolean(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Check if user exists
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

        if (Auth::check() && ! in_array('super_admin', Auth::user()->roles->pluck('name')->toArray())) {
            $query->where('shop_id', Auth::user()->shop_id);
        }

        return $query;
    }
}
