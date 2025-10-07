<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Models\Barang;
use App\Models\Supplier;

class EditBarangMasuk extends EditRecord
{
    protected static string $resource = BarangMasukResource::class;

    protected function getRedirectUrl(): string
    {
        // setelah berhasil create, kembali ke halaman index (list)
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $barang = Barang::where('namabarang', $data['namabarang'])->first();

        if ($barang && $barang->ketersediaan === 'tidak aktif') {
            Notification::make()
                ->title('Barang tidak aktif')
                ->body('Silahkan pilih barang yang lain')
                ->danger()
                ->persistent()
                ->send();

            $this->halt(); // batalkan update
        }
             $data['totalhargabeli'] = (int)$data['jumlahmasuk'] * (int)$data['hargabelieceran'];

        return $data;
    }
}
