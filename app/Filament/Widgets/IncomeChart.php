<?php

namespace App\Filament\Widgets;

use App\Enums\OrderPaymentEnum;
use Carbon\Carbon;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class IncomeChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Total Pendapatan Setiap Bulan';

    protected function getData(): array
    {
        $data = $this->getOrdersPerMonth();
        return [
            'datasets' => [
                [
                    'label' => 'Total Pendapatan',
                    'data' => $data['incomePerMonth']
                ],
            ],
            'labels' => $data['months'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function getOrdersPerMonth(): array
    {
        $now = Carbon::now();

        $incomePerMonth = [];
        $months = collect(range(1, 12))->map(function ($month) use ($now, &$incomePerMonth) {
            $totalIncome = OrderItem::whereHas('order', function ($query) use ($now, $month) {
                $query->whereMonth('created_at', Carbon::parse($now->month($month)->format('Y-m')))
                    ->where('status', '!=', OrderStatusEnum::DIBATALKAN->value)
                    ->where('payment', OrderPaymentEnum::LUNAS->value);
            })->sum(DB::raw('weight * unit_price'));

            $incomePerMonth[] = $totalIncome;

            return $now->month($month)->format('M');
        })->toArray();

        return [
            'incomePerMonth' => $incomePerMonth,
            'months' => $months,
        ];
    }
}
