<?php

namespace App\Filament\Resources;
use App\Filament\Resources\BarangResource\Pages;
use App\Filament\Resources\BarangResource\RelationManagers;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;

class BarangResource extends Resource
{
    protected static ?string $model = Barang::class;

    protected static ?string $navigationGroup = 'Manajemen Stok';
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Barang'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\TextInput::make('namabarang')
                ->label('Masukkan nama barang')
                ->required()
                ->maxLength(100),
            Forms\Components\Select::make('kategoribarang')
                ->label('Masukkan nama kategori')
                ->options(fn () => \App\Models\Kategori::query()
                    ->orderBy('kategoribarang')
                    ->pluck('kategoribarang', 'kategoribarang')
                    ->toArray())
                ->preload()
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('hargajualeceran')
                ->label('Masukkan harga barang')
                ->prefix('Rp. ')
                ->required()
                ->maxLength(11),
            Forms\Components\TextInput::make('stock')
                ->label('Masukkan stock barang')
                ->required()
                ->numeric()
                ->default(0) // atau 'aktif' untuk Select
                ->visibleOn('edit'),
            Forms\Components\Select::make('ketersediaan')
                ->options([
                    'aktif' => 'aktif',
                    'tidak aktif' => 'tidak aktif',
                ])
                ->searchable()
                ->native(false)
                ->default('aktif') // atau 'aktif' untuk Select
                ->visibleOn('edit'),
            Forms\Components\TextInput::make('name')
                ->label('Dikelola oleh')
                ->default(fn () => Auth::user()->name ?? '-')
                ->disabled() // tidak bisa diubah
                ->dehydrated() // tetap tersimpan di DB
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('namabarang')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('kategoribarang')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('hargajualeceran')
                    ->label('Harga jual eceran')
                    ->alignLeft() 
                    ->money('IDR', locale: 'id_ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('ketersediaan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Dikelola oleh')->sortable()->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
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
            'index' => Pages\ListBarangs::route('/'),
            'create' => Pages\CreateBarang::route('/create'),
            'edit' => Pages\EditBarang::route('/{record}/edit'),
        ];
    }
}
