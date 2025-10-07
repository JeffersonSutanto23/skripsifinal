<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use App\Filament\Resources\BarangResource;
use App\Filament\Resources\SupplierResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\Barang;
use App\Models\Supplier;

class CreateBarangMasuk extends CreateRecord
{
    protected static string $resource = BarangMasukResource::class;

    protected function getRedirectUrl(): string
    {
        // setelah berhasil create, kembali ke halaman index (list)
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $barang = Barang::where('namabarang', $data['namabarang'])->first();

        if ($barang && $barang->ketersediaan === 'tidak aktif') {
            Notification::make()
                ->title('Barang tidak aktif')
                ->body('Ubah status ke "aktif" di menu Barang untuk melanjutkan.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt(); // batalkan create
        }
    
         $data['totalhargabeli'] = (int)$data['jumlahmasuk'] * (int)$data['hargabelieceran'];

        
        return $data;
    }
}
