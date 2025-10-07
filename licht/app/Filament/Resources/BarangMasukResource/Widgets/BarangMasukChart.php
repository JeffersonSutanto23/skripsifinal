<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BarangMasukChart extends ChartWidget
{
    // Fix: Move Carbon::now() inside a function or use a computed property for dynamic heading.
    // For simplicity, we use the protected function getHeading() instead of a static property.

    public function getHeading(): string
    {
        $now = Carbon::now();
        return 'Grafik Barang Masuk bulan ' . $now->translatedFormat('F Y');
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
     * The custom canView() method is removed because the HasShieldPermissions trait 
     * on the User model and Filament Shield automatically handle widget permission checks.
     */

    protected function getData(): array
    {
        $now = Carbon::now();

        $rows = DB::table('barangmasuks')
            ->select('namabarang', DB::raw('SUM(jumlahmasuk) as total_masuk'))
            ->whereMonth('tanggalmasuk', $now->month)
            ->whereYear('tanggalmasuk', $now->year)
            ->groupBy('namabarang')
            ->orderByDesc('total_masuk')
            ->limit(12)
            ->get();

        $labels = $rows->pluck('namabarang')->all();
        $data   = $rows->pluck('total_masuk')->map(fn ($v) => (int) $v)->all();

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
                    'label'           => 'Jumlah Barang Masuk (Bulan ini)',
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