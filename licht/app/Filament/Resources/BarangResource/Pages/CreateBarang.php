<?php

namespace App\Filament\Resources\BarangResource\Pages;

use App\Filament\Resources\BarangResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBarang extends CreateRecord
{
    protected static string $resource = BarangResource::class;

    protected function getRedirectUrl(): string
    {
        // setelah berhasil create, kembali ke halaman index (list)
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['stock'] = 0;              // fix 0 saat create
        $data['ketersediaan'] = 'aktif'; // fix aktif saat create
        return $data;
    }
}
