<?php

namespace App\Filament\Resources;
use App\Filament\Resources\PelangganResource\Pages;
use App\Filament\Resources\PelangganResource\RelationManagers;
use App\Models\Pelanggan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;

class PelangganResource extends Resource
{
    protected static ?string $model = Pelanggan::class;

    protected static ?string $navigationGroup = 'Daftar Pelanggan & Supplier'; 
    protected static ?string $navigationIcon = 'heroicon-o-users'; 
    protected static ?string $navigationLabel = 'Pelanggan'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\TextInput::make('namapelanggan')
                ->label('Masukkan nama pelanggan')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('nomorpelanggan')
                ->label('Masukkan nomor pelanggan')
                ->required()
                ->maxLength(20),
            Forms\Components\TextInput::make('alamatpelanggan')
                ->label('Masukkan alamat pelanggan')
                ->required()
                ->maxLength(200),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('namapelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('nomorpelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('alamatpelanggan')->sortable()->searchable(),
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
            'index' => Pages\ListPelanggans::route('/'),
            'create' => Pages\CreatePelanggan::route('/create'),
            'edit' => Pages\EditPelanggan::route('/{record}/edit'),
        ];
    }
}
