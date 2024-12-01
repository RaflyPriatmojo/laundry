<?php

namespace App\Filament\Widgets;

use App\Enums\OrderPaymentEnum;
use App\Enums\OrderStatusEnum;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LastestOrders extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->query(OrderResource::getEloquentQuery())
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->weight(FontWeight::Bold)
                    ->rowIndex(),

                TextColumn::make('code')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('customer.name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Keterangan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Baru' => 'info',
                        'Proses' => 'warning',
                        'Diambil' => 'success',
                        'Dibatalkan' => 'danger',
                    })
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('items.weight')
                    ->label('Berat/Kg')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('items.unit_price')
                    ->label('Harga Paket')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->getStateUsing(function (Order $record) {
                        // Assuming you have a 'hasMany' relationship named 'items'
                        $orderItems = $record->items;

                        // Calculate total_price by summing up the total_price attribute of each OrderItem
                        $totalPrice = $orderItems->sum('total_price');

                        return 'Rp.' . number_format($totalPrice);
                    })
                    ->toggleable(),

                TextColumn::make('payment')
                    ->label('Status')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->toggleable()
                    ->date(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->toggleable()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Keterangan')
                    ->options([
                        'Baru' => OrderStatusEnum::BARU->value,
                        'Proses' => OrderStatusEnum::PROSES->value,
                        'Diambil' => OrderStatusEnum::DIAMBIL->value,
                        'Dibatalkan' => OrderStatusEnum::DIBATALKAN->value,
                    ])
                    ->native(false),

                SelectFilter::make('payment')
                    ->label('Status Pembayaran')
                    ->options([
                        'Belum Bayar' => OrderPaymentEnum::TERTUNDA->value,
                        'Lunas' => OrderPaymentEnum::LUNAS->value,
                    ])
                    ->native(false)
            ])
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                ActionGroup::make([
                    Action::make('Edit Status')
                        ->icon('heroicon-m-arrows-up-down')
                        ->form([
                            Select::make('status')
                                ->label('Keterangan')
                                ->required()
                                ->native(false)
                                ->options([
                                    'Baru' => OrderStatusEnum::BARU->value,
                                    'Proses' => OrderStatusEnum::PROSES->value,
                                    'Diambil' => OrderStatusEnum::DIAMBIL->value,
                                    'Dibatalkan' => OrderStatusEnum::DIBATALKAN->value,
                                ])
                                ->default(function (Order $record) {
                                    $status = null;
                                    if ($record->status === OrderStatusEnum::BARU->value) {
                                        $status = 'Baru';
                                    } elseif ($record->status === OrderStatusEnum::PROSES->value) {
                                        $status = 'Proses';
                                    } elseif ($record->status === OrderStatusEnum::DIAMBIL->value) {
                                        $status = 'Diambil';
                                    } elseif ($record->status === OrderStatusEnum::DIBATALKAN->value) {
                                        $status = 'Dibatalkan';
                                    }

                                    return $status;
                                }),

                            Select::make('payment')
                                ->label('Status Pembayaran')
                                ->required()
                                ->native(false)
                                ->options([
                                    'Belum Bayar' => OrderPaymentEnum::TERTUNDA->value,
                                    'Lunas' => OrderPaymentEnum::LUNAS->value,
                                ])
                                ->default(function (Order $payment) {
                                    $payment = null;
                                    if ($payment = OrderPaymentEnum::TERTUNDA->value) {
                                        $payment = 'Belum Bayar';
                                    } elseif ($payment = OrderPaymentEnum::LUNAS->value) {;
                                        $payment = 'Lunass';
                                    }

                                    return $payment;
                                }),

                            MarkdownEditor::make('notes')
                                ->label('Catatan')
                                ->default(function (Order $record) {
                                    return $record->notes ?? '';
                                }),
                        ])
                        ->action(function (Order $order, array $data): void {
                            $order->status = $data['status'];
                            $order->payment = $data['payment'];
                            $order->notes = $data['notes'];
                            $order->save();

                            Notification::make()
                                ->title('Updated Order Status and Note')
                                ->success()
                                ->send();
                        }),
                    Action::make('Cancel Order')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(function (Order $order): void {
                            $order->status = OrderStatusEnum::DIBATALKAN->value;
                            $order->save();

                            Notification::make()
                                ->title('Order Cancelled')
                                ->success()
                                ->send();
                        })
                        ->hidden(function (Order $order) {
                            return $order->status === OrderStatusEnum::DIBATALKAN->value;
                        })
                ])
                    ->tooltip('Opsi')
                    ->icon('heroicon-m-ellipsis-horizontal'),
            ]);
    }
}
