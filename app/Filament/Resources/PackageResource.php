<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationGroup = 'Laundry Corner';
    protected static ?int $navigationSort = 0;
    protected static ?string $recordTitleAttribute = 'name';
    protected static int $globalSearchResultsLimit = 5;
    protected static ?string $modelLabel = 'paket';
    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::count();
    // }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Fieldset::make('Detail Paket')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Paket')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        if ($operation !== 'create') {
                                            return;
                                        }

                                        $set('slug', Str::slug($state));
                                    })
                                    ->columnSpan([
                                        'sm' => 12,
                                        'xl' => 4,
                                        '2xl' => 4,
                                    ]),

                                TextInput::make('slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->unique(Package::class, 'slug', ignoreRecord: true)
                                    ->columnSpan([
                                        'sm' => 12,
                                        'xl' => 4,
                                        '2xl' => 4,
                                    ]),

                                TextInput::make('price')
                                    ->label('Harga')
                                    ->numeric()
                                    ->rules('regex:/^\d{1,6}(\.\d{0,2})?$/')
                                    ->required()
                                    ->columnSpan([
                                        'sm' => 12,
                                        'xl' => 4,
                                        '2xl' => 4,
                                    ]),

                                FileUpload::make('image')
                                    ->label('Gambar')
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Gambar bersifat opsional')
                                    ->directory('assets/paket')
                                    ->preserveFilenames()
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        null,
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->maxSize(5120)
                                    ->downloadable()
                                    ->openable()
                                    ->columnSpanFull()
                            ])->columns(12),
                    ]),

                Group::make()
                    ->schema([
                        Fieldset::make('Status')
                            ->schema([
                                Toggle::make('is_visible')
                                    ->label('Visibilitas')
                                    ->helperText('Mengaktifkan atau menonaktifkan visibilitas paket')
                                    ->default(true),

                                Toggle::make('is_featured')
                                    ->label('Unggulan')
                                    ->helperText('Mengaktifkan atau menonaktifkan status unggulan paket'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar'),

                TextColumn::make('name')
                    ->label('Nama Paket')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('price')
                    ->label('Harga Paket')
                    ->toggleable(),

                IconColumn::make('is_visible')
                    ->label('Visibilitas')
                    ->toggleable()
                    ->boolean(),

                IconColumn::make('is_featured')
                    ->label('Unggulan')
                    ->toggleable()
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->toggleable()
                    ->since(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->toggleable()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_visible')
                    ->label('Visibilitas')
                    ->boolean()
                    ->trueLabel('Hanya Paket yang Terlihat')
                    ->falseLabel('Hanya Paket Tersembunyi')
                    ->native(false),

                TernaryFilter::make('is_featured')
                    ->label('Unggulan')
                    ->boolean()
                    ->trueLabel('Hanya Unggulan yang Terlihat')
                    ->falseLabel('Hanya Unggulan Tersembunyi')
                    ->native(false),
            ])
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->action(fn(Package $record) => $record->delete()),
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
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
