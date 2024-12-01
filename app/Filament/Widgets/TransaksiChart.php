<?php

namespace App\Filament\Widgets;

use App\Enums\OrderPaymentEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class TransaksiChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Total Transaksi Setiap Bulan';

    protected function getData(): array
    {
        $data = $this->getOrdersPerMonth();

        return [
            'datasets' => [
                [
                    'label' => 'Total Transaksi',
                    'data' => $data['ordersPerMonth']
                ],
            ],
            'labels' => $data['months'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function getOrdersPerMonth(): array
    {
        $now = Carbon::now();

        $ordersPerMonth = [];
        $months = collect(range(1, 12))->map(function ($month) use ($now, &$ordersPerMonth) {
            $count = Order::whereMonth('created_at', Carbon::parse($now->month($month)->format('Y-m')))
                ->where('status', '!=', OrderStatusEnum::DIBATALKAN->value)
                ->where('payment', OrderPaymentEnum::LUNAS->value)
                ->count();
            $ordersPerMonth[] = $count;

            return $now->month($month)->format('M');
        })->toArray();

        return [
            'ordersPerMonth' => $ordersPerMonth,
            'months' => $months,
        ];
    }
}
