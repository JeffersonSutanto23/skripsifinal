<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PenjualanStat extends BaseWidget
{
    // Auto refresh setiap 5 detik (opsional, bisa dihapus jika tidak perlu)
    protected static ?string $pollingInterval = '10s';
    
    // Heading dan grid
    public function getHeading(): string
    {
        $now = Carbon::now();
        return 'Statistik Penjualan ' . $now->translatedFormat('F Y');
    }
    
    protected array|string|int $columnSpan = 4;

    /**
     * Cek apakah user saat ini boleh melihat widget ini.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $base = class_basename(static::class);
        $snake = Str::snake($base);

        $candidates = [
            "widget_{$base}",
            "view_widget_{$base}",
            "view_{$base}_widget",
            "widget_{$snake}",
            "view_widget_{$snake}",
            "view_{$snake}_widget",
            "view_{$base}",
            "view_{$snake}",
        ];

        foreach ($candidates as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function getStats(): array
    {
        $now = Carbon::now();

        // ===========================================
        // 1. Perhitungan Modal Terpakai (HPP) Bulan Ini
        // HANYA menggunakan harga beli dari barang masuk BULAN INI
        // ===========================================
        
        // Ambil harga beli HANYA dari bulan ini
        $costMapBulanIni = DB::table('barangmasuks')
            ->whereMonth('tanggalmasuk', $now->month)
            ->whereYear('tanggalmasuk', $now->year)
            ->select('namabarang', DB::raw('SUM(totalhargabeli) / NULLIF(SUM(jumlahmasuk),0) AS modal_satuan'))
            ->groupBy('namabarang')
            ->pluck('modal_satuan', 'namabarang');

        // Jika barang tidak ada di bulan ini, ambil dari data terakhir sebelum bulan ini
        $costMapSebelumnya = DB::table('barangmasuks')
            ->where('tanggalmasuk', '<', $now->startOfMonth())
            ->select('namabarang', DB::raw('SUM(totalhargabeli) / NULLIF(SUM(jumlahmasuk),0) AS modal_satuan'))
            ->groupBy('namabarang')
            ->pluck('modal_satuan', 'namabarang');

        // Gabungkan: prioritas harga bulan ini, fallback ke harga sebelumnya
        $costMap = $costMapBulanIni->merge($costMapSebelumnya->diffKeys($costMapBulanIni));

        $dataBarangKeluar = DB::table('barangkeluars')
            ->whereMonth('tanggalkeluar', $now->month)
            ->whereYear('tanggalkeluar', $now->year)
            ->get();

        $hppBulanIni = 0;
        foreach ($dataBarangKeluar as $r) {
            $qty         = (int) ($r->jumlahkeluar ?? 0);
            $modalSatuan = (float) ($costMap[$r->namabarang] ?? 0);
            $hppBulanIni += (int) round($modalSatuan * $qty);
        }

        // ===========================================
        // 2. Perhitungan Total Modal Masuk Bulan Ini
        // ===========================================
        $totalModalMasukBulanIni = DB::table('barangmasuks')
            ->whereMonth('tanggalmasuk', $now->month)
            ->whereYear('tanggalmasuk', $now->year)
            ->sum('totalhargabeli');

        // ===========================================
        // 3. Statistik Penjualan & Keuntungan
        // ===========================================
        $totalPenjualan = DB::table('barangkeluars')
            ->whereMonth('tanggalkeluar', $now->month)
            ->whereYear('tanggalkeluar', $now->year)
            ->sum(DB::raw('jumlahkeluar * hargajualeceran'));

        $totalKeuntungan = $totalPenjualan - $hppBulanIni;
        $profitColor     = $totalKeuntungan >= 0 ? 'success' : 'danger';
        $profitIcon      = $totalKeuntungan >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down';

        return [
            Stat::make('Total Keuntungan', 'Rp ' . number_format($totalKeuntungan, 0, ',', '.'))
                ->description('Laba/Rugi bersih bulan ' . $now->translatedFormat('F'))
                ->descriptionIcon($profitIcon)
                ->color($profitColor),

            Stat::make('Total Modal Bulan Ini', 'Rp ' . number_format($totalModalMasukBulanIni, 0, ',', '.'))
                ->description('Total biaya pembelian bulan ' . $now->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),

            Stat::make('Total Penjualan', 'Rp ' . number_format($totalPenjualan, 0, ',', '.'))
                ->description('Pendapatan kotor bulan ' . $now->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Modal Terpakai (HPP)', 'Rp ' . number_format($hppBulanIni, 0, ',', '.'))
                ->description('Modal yang terpakai bulan ' . $now->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-wallet')
                ->color('info'),
        ];
    }
}