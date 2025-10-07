<?php

namespace App\Filament\Resources\BarangKeluarResource\Pages;

use App\Filament\Resources\BarangKeluarResource;
use App\Filament\Resources\BarangResource;
use App\Filament\Resources\PelangganResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\Barang;
use App\Models\Pelanggan;

class CreateBarangKeluar extends CreateRecord
{
    protected static string $resource = BarangKeluarResource::class;

    protected function getRedirectUrl(): string
    {
        // setelah berhasil create, kembali ke halaman index (list)
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $barang = Barang::where('namabarang', $data['namabarang'])->first();

        if (! $barang) {
            Notification::make()
                ->title('Barang tidak ditemukan')
                ->body('Nama barang tidak terdaftar.')
                ->danger()->persistent()->send();
            $this->halt();
        }

        if ($barang->ketersediaan === 'tidak aktif') {
            Notification::make()
                ->title('Barang tidak aktif')
                ->body('Ubah status ke "aktif" di menu Barang untuk melanjutkan.')
                ->danger()->persistent()->send();
            $this->halt();
        }

        // Cek stok hanya saat benar-benar keluar (status "sudah sampai")
        if (($data['keterangan'] ?? null) === 'sudah sampai') {
            $stokTersedia = (int) $barang->stock;
            $qty          = (int) $data['jumlahkeluar'];

            if ($stokTersedia <= 0) {
                Notification::make()
                    ->title('Stok kosong')
                    ->body('Stok barang 0. Transaksi keluar tidak dapat diproses.')
                    ->danger()->persistent()->send();
                $this->halt();
            }

            if ($qty > $stokTersedia) {
                Notification::make()
                    ->title('Jumlah melebihi stok')
                    ->body("Stok tersedia: {$stokTersedia}. Kurangi jumlah atau tambah stok terlebih dahulu.")
                    ->warning()->persistent()->send();
                $this->halt();
            }
        }

         $data['totalhargajual'] = (int)$data['jumlahkeluar'] * (int)$data['hargajualeceran'];
        return $data;
    }
}
