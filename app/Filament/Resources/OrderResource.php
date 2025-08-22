<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    Select::make('shop_id')
                        ->relationship('shop', 'name')
                        ->required()
                        ->searchable(),

                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable(),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('customer_name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('customer_phone')
                        ->tel()
                        ->required(),
                ]),

                Grid::make(3)->schema([
                    TextInput::make('subtotal')->numeric()->required(),
                    TextInput::make('discount')->numeric()->default(0),
                    TextInput::make('total')->numeric()->required(),
                ]),

                Grid::make(2)->schema([
                    Select::make('status')
                        ->options([
                            'pending'   => 'Pending',
                            'completed' => 'Completed',
                            'canceled'  => 'Canceled',
                            'returned'  => 'Returned',
                        ])
                        ->default('pending')
                        ->required()
                        ->columnSpan(1),

                    Repeater::make('items')
                        ->relationship('items')
                        ->columnSpan(2)
                        ->schema([
                        Select::make('product_id')
                            ->relationship(
                                name: 'product',
                                titleAttribute: 'name_en',
                                modifyQueryUsing: fn ($query) => $query->when(Auth::user()?->shop_id, fn ($q, $sid) => $q->where('shop_id', $sid))
                            )
                            ->getOptionLabelFromRecordUsing(function (Product $record) {
                                return sprintf('%s — Stock: %d', $record->name_en, (int) $record->quantity);
                            })
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('unit_price', $product->sale_price);

                                        $quantity = $get('quantity') ?? 1;
                                        $set('total_price', $product->sale_price * $quantity);
                                    }
                                }
                            }),

                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $unitPrice = $get('unit_price') ?? 0;
                                $set('total_price', $unitPrice * $state);
                            }),

                        TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $quantity = $get('quantity') ?? 1;
                                $set('total_price', $state * $quantity);
                            }),

                        TextInput::make('total_price')
                            ->numeric()
                            ->required(),
                        ])
                        ->columns(4)
                        ->required()
                        ->createItemButtonLabel('Add Product'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('Order ID'),
                TextColumn::make('shop.name')->label('Shop'),
                TextColumn::make('user.name')->label('Created By'),
                TextColumn::make('customer_name'),
                TextColumn::make('customer_phone'),
                TextColumn::make('total')->money('PKR'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime('d M Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'completed' => 'Completed',
                        'canceled'  => 'Canceled',
                        'returned'  => 'Returned',
                    ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('manage_orders');
    }
}
