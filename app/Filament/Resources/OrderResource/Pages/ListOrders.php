<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Order'),
        ];
    }

    /**
     * Add custom table filters
     */
    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->options([
                    'pending'   => 'Pending',
                    'completed' => 'Completed',
                    'canceled'  => 'Canceled',
                    'returned'  => 'Returned',
                ])
                ->label('Order Status'),

            Filter::make('today')
                ->label('Today’s Orders')
                ->query(fn ($query) => $query->whereDate('created_at', now()->toDateString())),
        ];
    }

    /**
     * Default sort (latest orders first)
     */
    protected function getDefaultTableSortColumn(): ?string
    {
        return 'created_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }
}
