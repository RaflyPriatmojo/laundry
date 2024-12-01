<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationGroup = 'Laundry Corner';
    protected static ?int $navigationSort = 1;
    // protected static ?string $recordTitleAttribute = 'name';
    // protected static int $globalSearchResultsLimit = 5;
    protected static ?string $modelLabel = 'pelanggan';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Detail Pelanggan')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->columnSpan([
                                'sm' => 12,
                                'xl' => 4,
                                '2xl' => 4,
                            ]),

                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki - laki' => 'Laki - laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->native(false)
                            ->required()
                            ->columnSpan([
                                'sm' => 12,
                                'xl' => 4,
                                '2xl' => 4,
                            ]),

                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->required()
                            ->numeric()
                            ->columnSpan([
                                'sm' => 12,
                                'xl' => 4,
                                '2xl' => 4,
                            ]),

                        Textarea::make('address')
                            ->autosize()
                            ->required()
                            ->columnSpanFull()

                    ])->columns(12)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->toggleable(),

                TextColumn::make('address')
                    ->label('Alamat Lengkap')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Terdaftar Sejak')
                    ->toggleable()
                    ->since(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->toggleable()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    // ->label('Administrators only?')
                    // ->indicator('Administrators')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->action(fn (Customer $record) => $record->delete()),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
