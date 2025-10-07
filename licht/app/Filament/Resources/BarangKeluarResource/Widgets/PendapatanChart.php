<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PendapatanChart extends ChartWidget
{
    protected static ?string $pollingInterval = '10s';
    
    public ?string $filter = 'pendapatan';
    
    public function getHeading(): string
    {
        $now = Carbon::now();
        return "Grafik Penjualan {$now->year}";
    }
    
    protected array|string|int $columnSpan = 'full';
    
    // Filter untuk tipe grafik
    protected function getFilters(): ?array
    {
        return [
            'pendapatan' => 'Pendapatan',
            'modal' => 'Modal & HPP',
            'keuntungan' => 'Keuntungan',
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $base  = class_basename(static::class);
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

   // ...
protected function getData(): array
{
    $year = Carbon::now()->year;
    
    // --- HPP per Bulan (Same logic as before) ---
    $hppPerBulan = [];
    // ... (Your HPP calculation logic remains here)
    for ($m = 1; $m <= 12; $m++) {
        // ... (Your existing HPP calculation logic)
        // ... (Your existing HPP calculation logic)
        $bulanIni = Carbon::createFromDate($year, $m, 1);
        $costMapBulanIni = DB::table('barangmasuks')
            ->whereMonth('tanggalmasuk', $m)
            ->whereYear('tanggalmasuk', $year)
            ->select('namabarang', DB::raw('SUM(totalhargabeli) / NULLIF(SUM(jumlahmasuk),0) AS modal_satuan'))
            ->groupBy('namabarang')
            ->pluck('modal_satuan', 'namabarang');

        $costMapSebelumnya = DB::table('barangmasuks')
            ->where('tanggalmasuk', '<', $bulanIni->copy()->startOfMonth())
            ->select('namabarang', DB::raw('SUM(totalhargabeli) / NULLIF(SUM(jumlahmasuk),0) AS modal_satuan'))
            ->groupBy('namabarang')
            ->pluck('modal_satuan', 'namabarang');

        $costMap = $costMapBulanIni->merge($costMapSebelumnya->diffKeys($costMapBulanIni));

        $dataBarangKeluar = DB::table('barangkeluars')
            ->whereMonth('tanggalkeluar', $m)
            ->whereYear('tanggalkeluar', $year)
            ->get();

        $totalHpp = 0;
        foreach ($dataBarangKeluar as $item) {
            $qty = (int) ($item->jumlahkeluar ?? 0);
            $modalSatuan = (float) ($costMap[$item->namabarang] ?? 0);
            $totalHpp += (int) round($modalSatuan * $qty);
        }
        $hppPerBulan[$m] = $totalHpp;
    }

    // --- Pendapatan & Modal (Same logic as before) ---
    $pendapatan = DB::table('barangkeluars')
        ->selectRaw('MONTH(tanggalkeluar) as month, SUM(hargajualeceran * jumlahkeluar) as total')
        ->whereYear('tanggalkeluar', $year)
        ->groupBy('month')
        ->pluck('total', 'month')
        ->all();

    $totalModalMasuk = DB::table('barangmasuks')
        ->selectRaw('MONTH(tanggalmasuk) as month, SUM(totalhargabeli) as total')
        ->whereYear('tanggalmasuk', $year)
        ->groupBy('month')
        ->pluck('total', 'month')
        ->all();

    $labels = [];
    $dataPendapatan = [];
    $dataModalMasuk = [];
    $dataHPP = [];
    $dataKeuntungan = []; // This array holds the continuous trend line data
    $dataKeuntunganUntung = []; // Keep these for the scatter points only (if needed)
    $dataKeuntunganRugi = [];

    for ($m = 1; $m <= 12; $m++) {
        $labels[] = Carbon::createFromDate($year, $m, 1)->translatedFormat('M');

        $pnd = isset($pendapatan[$m]) ? (float) $pendapatan[$m] : 0;
        $mdl = isset($totalModalMasuk[$m]) ? (float) $totalModalMasuk[$m] : 0;
        $hpp = isset($hppPerBulan[$m]) ? (float) $hppPerBulan[$m] : 0;
        $keu = $pnd - $hpp; 

        $dataPendapatan[] = $pnd;
        $dataModalMasuk[] = $mdl;
        $dataHPP[] = $hpp;
        $dataKeuntungan[] = $keu; // Continuous data for the line

        // Logic for scatter points (only one point per month)
        if ($keu > 0) {
            $dataKeuntunganUntung[] = $keu;
            $dataKeuntunganRugi[] = null;
        } else if ($keu < 0) {
            $dataKeuntunganUntung[] = null;
            $dataKeuntunganRugi[] = $keu;
        } else {
            $dataKeuntunganUntung[] = null;
            $dataKeuntunganRugi[] = null;
        }
    }

    // Return data berdasarkan filter yang dipilih
    return match($this->filter) {
        'pendapatan' => [
            'datasets' => [
                // ... (dataPendapatan dataset)
                [
                    'label' => 'Pendapatan',
                    'data' => $dataPendapatan,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6,182,212,0.1)',
                    'fill' => false,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ],
        'modal' => [
            'datasets' => [
                // ... (dataModalMasuk dataset)
                [
                    'label' => 'Total Modal Masuk',
                    'data' => $dataModalMasuk,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245,158,11,0.1)',
                    'fill' => false,
                    'borderWidth' => 2,
                ],
                // ... (dataHPP dataset)
                [
                    'label' => 'Modal Terpakai (HPP)',
                    'data' => $dataHPP,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6,182,212,0.1)',
                    'fill' => false,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ],
        'keuntungan' => [
            'datasets' => [
                // 1. Dataset untuk GARIS TRENDING (Keuntungan/Kerugian)
                [
                    'label' => 'Tren Keuntungan',
                    'data' => $dataKeuntungan, // Use the continuous data
                    'borderColor' => '#f59e0b', // A neutral color for the trend line
                    'backgroundColor' => 'rgba(245,158,11,0.1)',
                    'fill' => false,
                    'borderWidth' => 2,
                    'pointRadius' => 0, // Make the line's default point invisible
                ],
                // 2. Dataset untuk TITIK (Untung - Hijau)
                [
                    'label' => 'Untung',
                    'data' => $dataKeuntunganUntung, // Only positive points
                    'borderColor' => '#00fe04ff',
                    'backgroundColor' => '#00fe04ff',
                    'type' => 'scatter', // Use scatter type for points on a line chart
                    'showLine' => false, // Ensure no line is drawn for these points
                    'pointRadius' => 5, // Make the point visible
                    'borderWidth' => 2,
                ],
                // 3. Dataset untuk TITIK (Rugi - Merah)
                [
                    'label' => 'Rugi',
                    'data' => $dataKeuntunganRugi, // Only negative points
                    'borderColor' => '#ff0803ff',
                    'backgroundColor' => '#ff0803ff',
                    'type' => 'scatter', // Use scatter type for points on a line chart
                    'showLine' => false, // Ensure no line is drawn for these points
                    'pointRadius' => 5, // Make the point visible
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ],
    };
}

protected function getType(): string
{
    // Keeping 'line' as the default chart type to enable the line dataset
    return 'line';
}
}