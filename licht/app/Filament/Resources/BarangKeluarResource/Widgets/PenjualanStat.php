<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Filament\Resources\BarangResource;
use Filament\Notifications\Actions\Action as NotificationAction;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PenjualanStat extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    public function getHeading(): string
    {
        $now = Carbon::now();
        return 'Statistik Penjualan ' . $now->translatedFormat('F Y');
    }

    protected array|string|int $columnSpan = 4;

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user) {
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

    public function mount(): void
    {
        $rows = DB::table('barangs')
            ->select('namabarang', 'stock')
            ->orderByDesc('stock')
            ->limit(50)
            ->get();

        $low = $rows->filter(fn ($r) => (int) $r->stock < 20);
        if ($low->isEmpty()) {
            return;
        }

        $userId = auth()->id() ?? 'guest';
        $key    = 'dash_low_stock:' . $userId . ':' . md5(url()->current());

        if (! Cache::add($key, true, now()->addSeconds(60))) {
            return;
        }

        $show    = $low->pluck('namabarang')->take(5)->implode(', ');
        $moreTxt = $low->count() > 5 ? ' dan ' . ($low->count() - 5) . ' barang lainnya' : '';

        Notification::make()
            ->title('Stok menipis')
            ->body("Ada barang yang stoknya menipis: {$show}{$moreTxt}.")
            ->danger()
            ->actions([
                NotificationAction::make('Lihat daftar')
                    ->url(BarangResource::getUrl())
                    ->button(),
            ])
            ->send();
    }

    protected function getStats(): array
    {
        $now = Carbon::now();

        // ==========================================================
        // 1. Ambil harga beli terbaru bulan ini per barang
        // ==========================================================
        $costMapNow = DB::table('barangmasuks as bm')
            ->select('bm.namabarang', 'bm.hargabelieceran')
            ->where('bm.keterangan', '!=', 'masih dalam proses')
            ->whereMonth('bm.tanggalmasuk', $now->month)
            ->whereYear('bm.tanggalmasuk', $now->year)
            ->whereRaw('bm.tanggalmasuk = (
                SELECT MAX(bm2.tanggalmasuk)
                FROM barangmasuks bm2
                WHERE bm2.namabarang = bm.namabarang
                  AND bm2.keterangan != "masih dalam proses"
                  AND MONTH(bm2.tanggalmasuk) = ?
                  AND YEAR(bm2.tanggalmasuk) = ?
            )', [$now->month, $now->year])
            ->pluck('bm.hargabelieceran', 'bm.namabarang')
            ->map(fn($v) => (float) $v)
            ->toArray();

        // ==========================================================
        // 2. Ambil harga beli terakhir sebelum bulan ini
        // ==========================================================
        $costMapLast = DB::table('barangmasuks as bm')
            ->select('bm.namabarang', 'bm.hargabelieceran')
            ->where('bm.keterangan', '!=', 'masih dalam proses')
            ->whereRaw('bm.tanggalmasuk = (
                SELECT MAX(bm2.tanggalmasuk)
                FROM barangmasuks bm2
                WHERE bm2.namabarang = bm.namabarang
                  AND bm2.keterangan != "masih dalam proses"
                  AND bm2.tanggalmasuk < ?
            )', [$now->startOfMonth()])
            ->pluck('bm.hargabelieceran', 'bm.namabarang')
            ->map(fn($v) => (float) $v)
            ->toArray();

        // ==========================================================
        // 3. Ambil data barang keluar bulan ini (selesai)
        // ==========================================================
        $barangKeluar = DB::table('barangkeluars')
            ->where('keterangan', '!=', 'masih dalam proses')
            ->whereMonth('tanggalkeluar', $now->month)
            ->whereYear('tanggalkeluar', $now->year)
            ->get();

        // ==========================================================
        // 4. Hitung HPP (logika: jika ada modal bulan ini pakai itu, jika tidak, pakai terakhir sebelumnya)
        // ==========================================================
        $hppBulanIni = 0;

        foreach ($barangKeluar as $item) {
            $qty = (int) ($item->jumlahkeluar ?? 0);
            $hargaBeli = $costMapNow[$item->namabarang]
                ?? $costMapLast[$item->namabarang]
                ?? 0;

            $hppBulanIni += $qty * (float) $hargaBeli;
        }

        // ==========================================================
        // 5. Total modal masuk bulan ini (selesai)
        // ==========================================================
        $totalModalMasukBulanIni = DB::table('barangmasuks')
            ->whereMonth('tanggalmasuk', $now->month)
            ->whereYear('tanggalmasuk', $now->year)
            ->where('keterangan', '!=', 'masih dalam proses')
            ->sum('totalhargabeli');

        // ==========================================================
        // 6. Total penjualan bulan ini (selesai)
        // ==========================================================
        $totalPenjualan = DB::table('barangkeluars')
            ->where('keterangan', '!=', 'masih dalam proses')
            ->whereMonth('tanggalkeluar', $now->month)
            ->whereYear('tanggalkeluar', $now->year)
            ->sum(DB::raw('jumlahkeluar * hargajualeceran'));

        // ==========================================================
        // 7. Hitung laba/rugi bersih
        // ==========================================================
        $totalKeuntungan = $totalPenjualan - $hppBulanIni;
        $profitColor = $totalKeuntungan >= 0 ? 'success' : 'danger';
        $profitIcon  = $totalKeuntungan >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down';

        // ==========================================================
        // 8. Return hasil statistik
        // ==========================================================
        return [
            Stat::make('Total Keuntungan', 'Rp ' . number_format($totalKeuntungan, 0, ',', '.'))
                ->description('Laba/Rugi bersih bulan ' . $now->translatedFormat('F'))
                ->descriptionIcon($profitIcon)
                ->color($profitColor),

            Stat::make('Total Modal Bulan Ini', 'Rp ' . number_format($totalModalMasukBulanIni, 0, ',', '.'))
                ->description('Total pembelian bulan ' . $now->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),

            Stat::make('Total Penjualan', 'Rp ' . number_format($totalPenjualan, 0, ',', '.'))
                ->description('Total Pendapatan bulan ' . $now->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Modal Terpakai (HPP)', 'Rp ' . number_format($hppBulanIni, 0, ',', '.'))
                ->description('Modal yang terpakai bulan ' . $now->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-wallet')
                ->color('info'),
        ];
    }
}
