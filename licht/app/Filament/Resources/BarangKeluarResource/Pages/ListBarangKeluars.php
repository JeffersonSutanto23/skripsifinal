<?php

namespace App\Filament\Resources\BarangKeluarResource\Pages;

use App\Filament\Resources\BarangKeluarResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;

class ListBarangKeluars extends ListRecords
{
    protected static string $resource = BarangKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('cetak_laporan')
                ->label('Cetak Laporan Barang Keluar')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Cetak Laporan Barang Keluar')
                ->modalSubheading('Apakah Anda yakin ingin mencetak laporan ini?')
                ->action(fn (): StreamedResponse => $this->cetakLaporan()),
        ];
    }

    protected function cetakLaporan(): StreamedResponse
    {
        // Baris yang tampil di tabel (ikut semua filter/sort)
        $rows = $this->getFilteredTableQuery()
            ->orderBy('tanggalkeluar')
            ->get([
                'namabarang','namapelanggan','jumlahkeluar',
                'hargajualeceran','totalhargajual','keterangan','tanggalkeluar',
            ]);

        $filters = $this->getTableFiltersForm()->getState();

        // Tentukan periode (start, end, label) dari filter yg aktif / range data
        [$start, $end, $periodeLabel] = $this->resolvePeriod($filters, 'tanggalkeluar', $rows);

        // Total penjualan dari baris yang tampil (persis dgn tabel)
        $totalPenjualan = (int) $rows->sum('totalhargajual');

        // Total modal di periode yang sama (ambil dari BarangMasuk)
        $totalModal = (int) BarangMasuk::query()
            ->when($start && $end, fn ($q) => $q->whereBetween('tanggalmasuk', [$start, $end]))
            ->sum('totalhargabeli');

        $totalBiaya   = 0;
        $labaKotor    = $totalPenjualan - $totalModal;
        $labaBersih   = $labaKotor - $totalBiaya;
        $marginKotor  = $totalPenjualan > 0 ? ($labaKotor  / $totalPenjualan * 100) : 0;
        $marginBersih = $totalPenjualan > 0 ? ($labaBersih / $totalPenjualan * 100) : 0;

        // Status Untung/Rugi/Break-even
        $profitLabel  = 'Break-even';
        $profitAmount = 0;
        if ($totalPenjualan > $totalModal) {
            $profitLabel  = 'Untung';
            $profitAmount = $totalPenjualan - $totalModal;
        } elseif ($totalPenjualan < $totalModal) {
            $profitLabel  = 'Rugi';
            $profitAmount = $totalModal - $totalPenjualan;
        }

        $pdf = \PDF::loadView('laporan.cetakbarangkeluar', [
            'data'            => $rows,
            'periode'         => $periodeLabel,
            'printedAt'       => now()->locale('id')->translatedFormat('j F Y'),
            'totalPenjualan'  => $totalPenjualan,
            'totalModal'      => $totalModal,
            'totalBiaya'      => $totalBiaya,
            'labaKotor'       => $labaKotor,
            'labaBersih'      => $labaBersih,
            'marginKotor'     => $marginKotor,
            'marginBersih'    => $marginBersih,
            'profitLabel'     => $profitLabel,
            'profitAmount'    => $profitAmount,
        ])->setPaper('a4', 'portrait');

        // KEMBALIKAN StreamedResponse
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'laporan-barang-keluar-' . now()->format('Ymd-His') . '.pdf'
        );
    }

    /**
     * Ambil start/end periode + label berdasarkan filter per_bulan/per_tahun.
     * Fallback: pakai min/max tanggal dari rows.
     */
    private function resolvePeriod(array $filters, string $dateCol, Collection $rows): array
    {
        $m = $filters['per_bulan']['bulan'] ?? null;
        $y = $filters['per_bulan']['tahun'] ?? null;

        if ($m && $y) {
            $start = Carbon::create((int) $y, (int) $m, 1)->startOfDay();
            $end   = (clone $start)->endOfMonth()->endOfDay();
            $bulanId = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                        7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
            return [$start, $end, ($bulanId[(int) $m] ?? $m) . ' ' . $y];
        }

        if ($y) {
            $start = Carbon::create((int) $y, 1, 1)->startOfDay();
            $end   = Carbon::create((int) $y, 12, 31)->endOfDay();
            return [$start, $end, 'Tahun ' . $y];
        }

        if ($rows->isNotEmpty()) {
            $min = Carbon::parse($rows->min($dateCol))->startOfDay();
            $max = Carbon::parse($rows->max($dateCol))->endOfDay();

            if ($min->isSameDay($max)) {
                $label = $min->locale('id')->translatedFormat('j F Y');
            } elseif ($min->isSameMonth($max)) {
                $label = $min->locale('id')->translatedFormat('F Y');
            } elseif ($min->year === $max->year) {
                $label = $min->locale('id')->translatedFormat('j M').' s/d '.$max->locale('id')->translatedFormat('j M Y');
            } else {
                $label = $min->year.'–'.$max->year;
            }

            return [$min, $max, $label];
        }

        return [null, null, '—'];
    }
}
