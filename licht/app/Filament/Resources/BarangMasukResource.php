<?php

namespace App\Filament\Resources;
use App\Filament\Resources\BarangMasukResource\Pages;
use App\Filament\Resources\BarangMasukResource\RelationManagers;
use App\Models\BarangMasuk;
use App\Models\Barang;
use App\Models\Supplier;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BarangMasukResource extends Resource
{
    protected static ?string $model = BarangMasuk::class;

    protected static ?string $navigationGroup = 'Manajemen Stok';
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Barang Masuk'; 

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            // === BARANG: preload semua opsi, tanpa loading ===
           // === BARANG: searchable TANPA loading (preload semua opsi) ===
            Forms\Components\Select::make('namabarang')
                ->label('Masukkan nama barang')
                ->options(fn () => \App\Models\Barang::query()
                    ->where('ketersediaan', 'aktif')
                    ->orderBy('namabarang')
                    ->pluck('namabarang', 'namabarang')
                    ->toArray())          // pastikan jadi array statis
                ->preload()               // muat semua opsi saat form render
                ->searchable()            // tetap bisa diketik (client-side filter)
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $barang = \App\Models\Barang::where('namabarang', $state)->first();
                    if ($barang && $barang->ketersediaan === 'tidak aktif') {
                        \Filament\Notifications\Notification::make()
                            ->title('Barang tidak aktif')
                            ->body('Barang ini berstatus tidak aktif dan tidak bisa dipakai untuk transaksi.')
                            ->danger()
                            ->send();
                    }
                }),

            // === SUPPLIER: searchable TANPA loading (preload semua opsi) ===
            Forms\Components\Select::make('namasupplier')
                ->label('Masukkan nama supplier')
                ->options(fn () => \App\Models\Supplier::query()
                    ->orderBy('namasupplier')
                    ->pluck('namasupplier', 'namasupplier')
                    ->toArray())
                ->preload()
                ->searchable()
                ->required(),
            // === Harga beli eceran ===
            Forms\Components\TextInput::make('hargabelieceran')
                ->label('Masukkan harga beli eceran')
                ->prefix('Rp. ')
                ->numeric()
                ->minValue(0)
                ->live()
                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                    $qty = (int) $get('jumlahmasuk');
                    $set('totalhargabeli', $qty * (int) $state);
                }),

            // === Jumlah masuk ===
            Forms\Components\TextInput::make('jumlahmasuk')
                ->label('Masukkan jumlah masuk')
                ->numeric()
                ->minValue(1)
                ->live()
                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                    $qty   = (int) preg_replace('/\D/', '', (string) $state);
                    $harga = (int) preg_replace('/\D/', '', (string) $get('hargabelieceran'));
                    $set('totalhargabeli', $qty * $harga);
                }),

            // === Total harga beli (otomatis) ===
            Forms\Components\TextInput::make('totalhargabeli')
                ->label('Masukkan total harga beli')
                ->prefix('Rp. ')
                ->numeric()
                ->readOnly()
                ->required(),

            // === Keterangan ===
            Forms\Components\Select::make('keterangan')
                ->label('Keterangan')
                ->options([
                    'sudah sampai' => 'sudah sampai',
                    'masih dalam proses' => 'masih dalam proses',
                ])
                ->preload()
                ->searchable()
                ->native(false),

            // === Tanggal (pakai DatePicker saja) ===
            Forms\Components\DateTimePicker::make('tanggalmasuk') 
            ->label('Tanggal Barang Masuk') 
            ->required() 
            ->format('Y-m-d') 
            ->placeholder('Pilih tanggal masuk') 
            ->columnSpan(1),
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
                Tables\Columns\TextColumn::make('namasupplier')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('jumlahmasuk')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('hargabelieceran')
                        ->label('Harga beli eceran')
                        ->alignLeft() 
                        ->money('IDR', locale: 'id_ID')
                        ->sortable()
                        ->searchable(),
                Tables\Columns\TextColumn::make('totalhargabeli')
                        ->label('Total harga beli')
                        ->alignLeft() 
                        ->money('IDR', locale: 'id_ID')
                        ->sortable()
                        ->searchable(),
                Tables\Columns\TextColumn::make('keterangan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('tanggalmasuk')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Dikelola oleh')->sortable()->searchable(),
            ])
           ->filters([
                Filter::make('tanggalmasuk')
                    ->label('Rentang tanggal')
                    ->form([
                        DatePicker::make('dari')
                            ->label('Dari tanggal'),
                        DatePicker::make('sampai')
                            ->label('Sampai tanggal')
                            ->default(now())   // default: hari ini
                            ->maxDate(now()),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $start = isset($data['dari']) && $data['dari']
                            ? Carbon::parse($data['dari'])->startOfDay()
                            : null;

                        $end = isset($data['sampai']) && $data['sampai']
                            ? Carbon::parse($data['sampai'])->endOfDay()
                            : Carbon::now()->endOfDay();

                        return $query
                            ->when($start, fn ($q) => $q->where('tanggalmasuk', '>=', $start))
                            ->when($end,   fn ($q) => $q->where('tanggalmasuk', '<=', $end));
                    }),
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
            'index' => Pages\ListBarangMasuks::route('/'),
            'create' => Pages\CreateBarangMasuk::route('/create'),
            'edit' => Pages\EditBarangMasuk::route('/{record}/edit'),
        ];
    }
}
