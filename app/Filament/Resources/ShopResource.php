<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopResource\Pages;
use App\Models\Shop;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ShopResource extends Resource
{
    protected static ?string $model = Shop::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Shop Name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('phone')
                ->tel()
                ->maxLength(20)
                ->label('Contact Phone'),

            Forms\Components\Textarea::make('address')
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('logo_path')
                ->image()
                ->directory('shop-logos')
                ->imageEditor()
                ->label('Shop Logo')
                ->columnSpanFull(),

            // Select existing user as Shop Owner (will be handled in CreateShop lifecycle)
            Forms\Components\Select::make('owner_user_id')
                ->label('Assign Shop Owner (Optional)')
                ->helperText('Optional: Select an existing user to assign as this shop\'s owner. You can also create users later and assign them to this shop.')
                ->searchable()
                ->preload()
                ->options(function () {
                    $shopId = request()->route('record');
                    return User::query()
                        ->when($shopId, function ($q) use ($shopId) {
                            $q->where(function ($qq) use ($shopId) {
                                $qq->whereNull('shop_id')
                                   ->orWhere('shop_id', $shopId);
                            });
                        }, function ($q) {
                            $q->whereNull('shop_id');
                        })
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->dehydrated(false)
                ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('trial_ends_at')
                ->label('Trial Ends At'),

            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'suspended' => 'Suspended',
                ])
                ->default('active')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable(),
            Tables\Columns\TextColumn::make('phone')
                ->label('Contact Phone')
                ->searchable(),
            Tables\Columns\TextColumn::make('owner.name')
                ->label('Owner')
                ->searchable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->colors([
                    'success' => 'active',
                    'danger' => 'inactive',
                    'warning' => 'suspended',
                ]),
            Tables\Columns\TextColumn::make('trial_ends_at')
                ->dateTime('d M, Y H:i'),
            Tables\Columns\TextColumn::make('created_at')->dateTime('d M, Y'),
            Tables\Columns\TextColumn::make('updated_at')->dateTime('d M, Y'),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    // 🚀 Relations Removed
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShops::route('/'),
            'create' => Pages\CreateShop::route('/create'),
            'edit' => Pages\EditShop::route('/{record}/edit'),
        ];
    }

    // 🚀 Spatie Permission Integration
    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->can('manage_shops');
    }

    public static function canCreate(): bool
    {
        return Auth::check() && Auth::user()->can('manage_shops');
    }

    public static function canEdit($record): bool
    {
        return Auth::check() && Auth::user()->can('manage_shops');
    }

    public static function canDelete($record): bool
    {
        return Auth::check() && Auth::user()->can('manage_shops');
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (!Auth::check()) return $query;
        
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Super Admins can see all shops
        if (in_array('super_admin', $userRoles)) {
            return $query;
        }
        
        // Shop Owners can only see their own shop
        if (in_array('shop_owner', $userRoles) && $user->shop_id) {
            return $query->where('id', $user->shop_id);
        }
        
        return $query;
    }
}
