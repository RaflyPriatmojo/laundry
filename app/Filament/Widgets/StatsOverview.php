<?php

namespace App\Filament\Widgets;

use App\Enums\OrderPaymentEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '15s';
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pelanggan', Customer::count())
                ->description('Peningkatan pelanggan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Total Transaksi Hari Ini', Order::where('status', '!=', OrderStatusEnum::DIBATALKAN->value)
                ->whereDate('created_at', today())
                ->count())
                ->description('Total transaksi')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Total Pendapatan', 'Rp. ' . number_format(OrderItem::whereHas('order', function ($query) {
                $query->where('payment', OrderPaymentEnum::LUNAS->value)
                    ->where('status', '!=', OrderStatusEnum::DIBATALKAN->value);
            })->sum(DB::raw('weight * unit_price'))))
                ->description('Total pendapatan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
        ];
    }
}
