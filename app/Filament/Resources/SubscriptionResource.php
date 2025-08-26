<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('shop_id')
                ->relationship('shop', 'name')
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('plan_id')
                ->relationship('plan', 'name')
                ->searchable()->preload()->required(),
            Forms\Components\DatePicker::make('start_date')->required(),
            Forms\Components\DatePicker::make('end_date')->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending Approval',
                    'active' => 'Active',
                    'expired' => 'Expired',
                    'canceled' => 'Canceled',
                    'deactivated' => 'Deactivated',
                ])->default('active')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.name')
                    ->searchable()->label('Shop'),
                Tables\Columns\TextColumn::make('plan.name')
                    ->searchable()->label('Plan'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'expired' => 'danger',
                        'canceled' => 'gray',
                        'deactivated' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Subscription $record) => $record->status === 'pending')
                    ->action(function (Subscription $record) {
                        $record->status = 'active';
                        $record->save();
                        Notification::make()
                            ->title('Subscription approved successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approve Subscription')
                    ->modalDescription('Are you sure you want to approve this subscription? This will activate the subscription immediately.')
                    ->modalSubmitActionLabel('Yes, Approve'),
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Subscription $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Subscription')
                    ->modalDescription('Are you sure you want to deactivate this subscription? The shop will lose access to premium features.')
                    ->modalSubmitActionLabel('Yes, deactivate')
                    ->action(function (Subscription $record) {
                        $record->status = 'deactivated';
                        $record->save();
                        Notification::make()
                            ->title('Subscription deactivated successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Subscription $record) => $record->status === 'deactivated')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Subscription')
                    ->modalDescription('Are you sure you want to activate this subscription? This will restore access to premium features for the shop.')
                    ->modalSubmitActionLabel('Yes, activate')
                    ->action(function (Subscription $record) {
                        $record->status = 'active';
                        $record->save();
                        Notification::make()
                            ->title('Subscription activated successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (!Auth::check()) return false;
        return Auth::user() && in_array('super_admin', Auth::user()->roles->pluck('name')->toArray());
    }

    public static function canCreate(): bool
    {
        if (!Auth::check()) return false;
        return Auth::user() && in_array('super_admin', Auth::user()->roles->pluck('name')->toArray());
    }

    public static function canEdit($record): bool
    {
        if (!Auth::check()) return false;
        return Auth::user() && in_array('super_admin', Auth::user()->roles->pluck('name')->toArray());
    }

    public static function canDelete($record): bool
    {
        if (!Auth::check()) return false;
        return Auth::user() && in_array('super_admin', Auth::user()->roles->pluck('name')->toArray());
    }
}
