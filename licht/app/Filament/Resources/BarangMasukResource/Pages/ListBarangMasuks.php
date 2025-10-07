<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use App\Filament\Resources\BarangResource;
use App\Filament\Resources\SupplierResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListBarangMasuks extends ListRecords
{
    protected static string $resource = BarangMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('cetak_laporan')
                ->label('Cetak Laporan Barang Masuk')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Cetak Laporan Barang Masuk')
                ->modalSubheading('Apakah Anda yakin ingin mencetak laporan ini?')
                ->action(fn (): StreamedResponse => $this->cetakLaporan()),
        ];
    }

   protected function cetakLaporan(): StreamedResponse
{
    $rows = $this->getFilteredTableQuery()
        ->orderBy('tanggalmasuk')
        ->get([
            'namabarang','namasupplier','jumlahmasuk',
            'hargabelieceran','totalhargabeli','keterangan','tanggalmasuk',
        ]);

    // Ambil state filter aktif & buat label periode
    $filters = $this->getTableFiltersForm()->getState();
    $periode = $this->labelPeriodeDariFilter($filters, 'tanggalmasuk', $rows);

    $pdf = \PDF::loadView('laporan.cetakbarangmasuk', [
        'data'      => $rows,
        'periode'   => $periode,    // << kirim ke Blade
        'printedAt' => now()->locale('id')->translatedFormat('j F Y'),
    ])->setPaper('a4', 'portrait');

    return response()->streamDownload(fn () => print($pdf->output()),
        'laporan-barang-masuk-'.now()->format('Ymd-His').'.pdf');
}

private function labelPeriodeDariFilter(array $filters, string $dateCol, Collection $rows): string
{
    $bulanId = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

    // Kamu saat ini punya filter "per_bulan" → pakai ini dulu
    $m = $filters['per_bulan']['bulan'] ?? null;
    $y = $filters['per_bulan']['tahun'] ?? null;
    if ($m && $y) return ($bulanId[(int)$m] ?? $m).' '.$y;
    if ($y)      return 'Tahun '.$y;

    // fallback: kalau tidak ada filter, ambil range dari data yang tampil
    if ($rows->isNotEmpty()) {
        $min = Carbon::parse($rows->min($dateCol));
        $max = Carbon::parse($rows->max($dateCol));
        if ($min->isSameDay($max))  return $min->locale('id')->translatedFormat('j F Y');
        if ($min->isSameMonth($max)) return $min->locale('id')->translatedFormat('F Y');
        if ($min->year === $max->year)
            return $min->locale('id')->translatedFormat('j M').' s/d '.$max->locale('id')->translatedFormat('j M Y');
        return $min->year.'–'.$max->year;
    }

    return '—';
}
}
