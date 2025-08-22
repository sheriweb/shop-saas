<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'User Management';


    public static function form(Form $form): Form
    {
        $isShopOwner = Auth::check() && in_array('shop_owner', Auth::user()?->roles->pluck('name')->toArray() ?? []);
        $isSuperAdmin = Auth::check() && in_array('super_admin', Auth::user()?->roles->pluck('name')->toArray() ?? []);
        
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => ! empty($state) ? bcrypt($state) : null)
                    ->required(fn (string $context): bool => $context === 'create'),

                // Shop selection with different behavior based on user role
                Forms\Components\Select::make('shop_id')
                    ->relationship('shop', 'name')
                    ->searchable()
                    ->preload()
                    ->label(fn () => $isSuperAdmin ? 'Assign to Shop (Optional)' : 'Shop')
                    ->helperText(fn () => $isSuperAdmin ? 'Leave empty if this user is not associated with any shop' : null)
                    ->required(fn () => $isShopOwner) // Required only for shop owners creating staff
                    ->visible(fn () => $isSuperAdmin) // Visible only to super admins
                    ->default(fn () => $isShopOwner ? Auth::user()->shop_id : null),
                    
                // Hidden shop_id field for Shop Owners creating staff
                Forms\Components\Hidden::make('shop_id')
                    ->default(fn () => $isShopOwner ? Auth::user()->shop_id : null)
                    ->visible(fn () => $isShopOwner),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),

                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active'),

                // Role selection - different options based on user type
                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name', function ($query) use ($isShopOwner) {
                        if ($isShopOwner) {
                            // Shop owners can only assign staff role
                            $query->where('name', 'staff');
                        }
                    })
                    ->preload()
                    ->label('Assign Roles')
                    ->helperText(fn () => $isSuperAdmin ? 
                        'Select appropriate roles. If assigning to a shop, consider shop_owner role.' : 
                        'Staff users will be assigned to your shop')
                    ->default(fn () => $isShopOwner ? ['staff'] : null)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('shop.name')->label('Shop')->sortable(),
                Tables\Columns\TextColumn::make('roles.name')->badge()->label('Roles'),
                Tables\Columns\TextColumn::make('status')->badge()->colors([
                    'success' => 'active',
                    'danger' => 'inactive',
                ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (!Auth::check()) return false;
        
        // Both Super Admins and Shop Owners can view users
        $userRoles = Auth::user()->roles->pluck('name')->toArray();
        return in_array('super_admin', $userRoles) || in_array('shop_owner', $userRoles);
    }

    public static function canCreate(): bool
    {
        if (!Auth::check()) return false;
        
        // Both Super Admins and Shop Owners can create users
        $userRoles = Auth::user()->roles->pluck('name')->toArray();
        return in_array('super_admin', $userRoles) || in_array('shop_owner', $userRoles);
    }

    public static function canEdit($record): bool
    {
        if (!Auth::check()) return false;
        
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Super Admins can edit any user
        if (in_array('super_admin', $userRoles)) {
            return true;
        }
        
        // Shop Owners can only edit staff users from their own shop
        if (in_array('shop_owner', $userRoles)) {
            // Check if record is a staff user and belongs to the same shop
            return $record->shop_id === $user->shop_id && 
                   in_array('staff', $record->roles->pluck('name')->toArray());
        }
        
        return false;
    }

    public static function canDelete($record): bool
    {
        if (!Auth::check()) return false;
        
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Super Admins can delete any user
        if (in_array('super_admin', $userRoles)) {
            return true;
        }
        
        // Shop Owners can only delete staff users from their own shop
        if (in_array('shop_owner', $userRoles)) {
            // Check if record is a staff user and belongs to the same shop
            return $record->shop_id === $user->shop_id && 
                   in_array('staff', $record->roles->pluck('name')->toArray());
        }
        
        return false;
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (!Auth::check()) return $query;
        
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Super Admins can see all users
        if (in_array('super_admin', $userRoles)) {
            return $query;
        }
        
        // Shop Owners can only see users from their own shop
        if (in_array('shop_owner', $userRoles) && $user->shop_id) {
            return $query->where('shop_id', $user->shop_id);
        }
        
        return $query;
    }
}
