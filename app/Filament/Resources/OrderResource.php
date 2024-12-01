<?php

namespace App\Filament\Resources;

use App\Enums\OrderPaymentEnum;
use App\Enums\OrderStatusEnum;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationGroup = 'Laundry Corner';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'code';
    protected static int $globalSearchResultsLimit = 5;
    protected static ?string $modelLabel = 'transaksi';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Customer Detail')
                        ->schema([
                            TextInput::make('code')
                                ->label('Kode Transaksi')
                                ->default('TRX-ID-' . date('ymd') . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT))
                                ->disabled()
                                ->dehydrated()
                                ->required(),

                            Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->preload()
                                ->live()
                                ->searchable()
                                ->required(),

                            Select::make('status')
                                ->label('Keterangan')
                                ->options([
                                    'Baru' => OrderStatusEnum::BARU->value,
                                    'Proses' => OrderStatusEnum::PROSES->value,
                                    'Diambil' => OrderStatusEnum::DIAMBIL->value,
                                    'Dibatalkan' => OrderStatusEnum::DIBATALKAN->value,
                                ])
                                ->native(false)
                                ->default('Baru')
                                ->required(),

                            Select::make('payment')
                                ->label('Status Pembayaran')
                                ->options([
                                    'Belum Bayar' => OrderPaymentEnum::TERTUNDA->value,
                                    'Lunas' => OrderPaymentEnum::LUNAS->value,
                                ])
                                ->native(false)
                                ->default('Belum Bayar')
                                ->required(),

                            MarkdownEditor::make('notes')
                                ->label('Catatan')
                                ->columnSpanFull()
                        ])->columns(2),

                    Step::make('Order Detail')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Select::make('package_id')
                                        ->label('Paket')
                                        ->options(Package::query()->pluck('name', 'id'))
                                        ->required()
                                        ->native(false)
                                        ->afterStateUpdated(fn($state, Set $set) =>
                                        $set('unit_price', Package::find($state)?->price ?? 0)),

                                    TextInput::make('weight')
                                        ->label('Berat/Kg')
                                        ->numeric()
                                        ->live()
                                        ->dehydrated()
                                        ->required(),

                                    TextInput::make('unit_price')
                                        ->label('Harga Paket')
                                        ->disabled()
                                        ->dehydrated()
                                        ->numeric()
                                        ->required(),

                                    Placeholder::make('total_price')
                                        ->label('Total Price')
                                        ->content(function ($get) {
                                            return 'Rp.' . number_format($get('weight') * $get('unit_price'));
                                        })
                                ])->columns(4)
                        ])
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
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
                    ->color(fn(string $state): string => match ($state) {
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
                fn(Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                ActionGroup::make([
                    ActionGroup::make([
                        ViewAction::make(),
                        EditAction::make(),
                        DeleteAction::make()
                    ])->dropdown(false),
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
                                ->default(function (Order $order) {
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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('heroicon-m-plus'),
            ])
            ->defaultPaginationPageOption(5);
        // ->reorderable('name');
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
