<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BarangKeluarChart extends ChartWidget
{
    // Heading changed to use a function for dynamic calculation and reflect quantity data.
    public function getHeading(): string
    {
        $now = Carbon::now();
        return 'Grafik Barang Keluar bulan ' . $now->translatedFormat('F Y');
    }

    protected array|string|int $columnSpan = [
        'default' => 1,
        'sm' => 1,
        'lg' => 1,
    ];

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

    /**
     * The custom canView() method has been removed. 
     * Since the User model uses HasShieldPermissions, 
     * Filament Shield will automatically handle widget permission checks.
     */

    protected function getData(): array
    {
        $now = Carbon::now();

        $rows = DB::table('barangkeluars')
            ->select('namabarang', DB::raw('SUM(jumlahkeluar) as total_keluar'))
            ->whereMonth('tanggalkeluar', $now->month)
            ->whereYear('tanggalkeluar', $now->year)
            ->groupBy('namabarang')
            ->orderByDesc('total_keluar')
            ->limit(12)
            ->get();

        $labels = $rows->pluck('namabarang')->all();
        $data   = $rows->pluck('total_keluar')->map(fn ($v) => (int) $v)->all();

        $palette = [
            '#ef4444','#f59e0b','#10b981','#3b82f6','#8b5cf6',
            '#ec4899','#14b8a6','#f97316','#22c55e','#a855f7',
            '#06b6d4','#eab308','#84cc16','#fb7185','#2dd4bf',
        ];

        $colors = [];
        for ($i = 0, $n = count($data); $i < $n; $i++) {
            $colors[] = $palette[$i % count($palette)];
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah Barang Keluar (Bulan ini)',
                    'data'            => $data,
                    'backgroundColor' => $colors,
                    'borderColor'     => $colors,
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
