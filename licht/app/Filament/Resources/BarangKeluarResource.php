<?php

namespace App\Filament\Resources;
use App\Filament\Resources\BarangKeluarResource\Pages;
use App\Filament\Resources\BarangKeluarResource\RelationManagers;
use App\Models\BarangKeluar;
use App\Models\Barang;
use App\Models\Pelanggan;
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
use Filament\Forms\Components\TextInput\Mask;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BarangKeluarResource extends Resource
{
    protected static ?string $model = BarangKeluar::class;
    protected static ?string $navigationGroup = 'Manajemen Stok';
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Barang Keluar'; 

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
                        ->toArray())
                    ->preload()
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        $barang = \App\Models\Barang::where('namabarang', $state)->first();

                        if ($barang) {
                            $set('hargajualeceran', (int) $barang->hargajualeceran);

                            $qty = (int) preg_replace('/\D/', '', (string) $get('jumlahkeluar'));
                            $set('totalhargajual', $qty * (int) $barang->hargajualeceran);
                        } else {
                            $set('hargajualeceran', null);
                            $set('totalhargajual', null);
                        }
                    }),
            // === SUPPLIER: searchable TANPA loading (preload semua opsi) ===
            Forms\Components\Select::make('namapelanggan')
                ->label('Masukkan nama pelanggan')
                ->options(fn () => \App\Models\Pelanggan::query()
                    ->orderBy('namapelanggan')
                    ->pluck('namapelanggan', 'namapelanggan')
                    ->toArray())
                ->preload()
                ->searchable()
                ->required(),
            // === Harga beli eceran ===
            Forms\Components\TextInput::make('hargajualeceran')
                    ->label('Masukkan harga jual eceran')
                    ->prefix('Rp. ')
                    ->numeric()
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        // kalau user menyesuaikan harga, total ikut update
                        $qty = (int) preg_replace('/\D/', '', (string) $get('jumlahkeluar'));
                        $set('totalhargajual', $qty * (int) $state);
                    }),
            // === Jumlah masuk ===
            Forms\Components\TextInput::make('jumlahkeluar')
                ->label('Masukkan jumlah keluar')
                ->numeric()
                ->minValue(1)
                ->live()
                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                    $qty   = (int) preg_replace('/\D/', '', (string) $state);
                    $harga = (int) preg_replace('/\D/', '', (string) $get('hargajualeceran'));
                    $set('totalhargajual', $qty * $harga);
                }),
            // === Total harga beli (otomatis) ===
            Forms\Components\TextInput::make('totalhargajual')
                ->label('Masukkan total harga jual')
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
                ->searchable()
                ->native(false),

            // === Tanggal (pakai DatePicker saja) ===
            Forms\Components\DateTimePicker::make('tanggalkeluar') 
            ->label('Tanggal Barang Keluar') 
            ->required() 
            ->format('Y-m-d') 
            ->placeholder('Pilih tanggal keluar') 
            ->columnSpan(1), 
            Forms\Components\TextInput::make('name')
                ->label('Dikelola oleh')
                ->disabled()
                ->dehydrated()
                ->required()
                ->afterStateHydrated(function ($component, $state) {
                    // selalu isi sesuai user login saat form dimuat (create/edit)
                    $component->state(Auth::user()->name ?? '-');
                    })
        ]);       
}


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('namabarang')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('namapelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('jumlahkeluar')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('hargajualeceran')
                    ->label('Harga jual eceran')
                    ->alignLeft() 
                    ->money('IDR', locale: 'id_ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('totalhargajual')
                    ->label('Total harga jual')
                    ->alignLeft() 
                    ->money('IDR', locale: 'id_ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('keterangan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('tanggalkeluar')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Dikelola oleh')->sortable()->searchable(),
            ])
           ->filters([
                Filter::make('tanggalkeluar')
                    ->label('Rentang tanggal')
                    ->form([
                        DatePicker::make('dari')
                            ->label('Dari tanggal'),
                        DatePicker::make('sampai')
                            ->label('Sampai tanggal')
                            ->default(now())
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
                            ->when($start, fn ($q) => $q->where('tanggalkeluar', '>=', $start))
                            ->when($end,   fn ($q) => $q->where('tanggalkeluar', '<=', $end));
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
            'index' => Pages\ListBarangKeluars::route('/'),
            'create' => Pages\CreateBarangKeluar::route('/create'),
            'edit' => Pages\EditBarangKeluar::route('/{record}/edit'),
        ];
    }
}
