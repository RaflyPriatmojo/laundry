<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatusEnum;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // public function getTabs(): array
    // {
    //     return [
    //         'Semua' => Tab::make(),
    //         'Baru' => Tab::make('Orderan Baru')
    //             ->modifyQueryUsing(fn ($query) => $query->where('status', OrderStatusEnum::BARU->value))
    //             ->badge(Order::query()->where('status', OrderStatusEnum::BARU->value)->count()),
    //         'Proses' => Tab::make('Orderan Diproses')
    //             ->modifyQueryUsing(fn ($query) => $query->where('status', OrderStatusEnum::PROSES->value))
    //             ->badge(Order::query()->where('status', OrderStatusEnum::PROSES->value)->count()),
    //         'Diambil' => Tab::make('Sudah Diambil')
    //             ->modifyQueryUsing(fn ($query) => $query->where('status', OrderStatusEnum::DIAMBIL->value))
    //             ->badge(Order::query()->where('status', OrderStatusEnum::DIAMBIL->value)->count()),
    //         'Dibatalkan' => Tab::make('Dibatalkan')
    //             ->modifyQueryUsing(fn ($query) => $query->where('status', OrderStatusEnum::DIBATALKAN->value))
    //             ->badge(Order::query()->where('status', OrderStatusEnum::DIBATALKAN->value)->count()),
    //     ];
    // }
}
