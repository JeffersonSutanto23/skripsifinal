<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Filament\Resources\BarangResource;
use Illuminate\Support\Str;

class BarangChart extends ChartWidget
{
    protected static ?string $heading = 'Stok Barang';

    protected array|string|int $columnSpan = [
        'default' => 1,
        'md' => 1,
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

    /** Kirim notifikasi SEKALI per kunjungan dashboard */
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

    protected function getData(): array
    {
        $rows = DB::table('barangs')
            ->select('namabarang', 'stock')
            ->orderByDesc('stock')
            ->limit(12)
            ->get();

        $labels = $rows->pluck('namabarang')->all();
        $data   = $rows->pluck('stock')->map(fn ($v) => (int) $v)->all();

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
            'datasets' => [[
                'label'           => 'Stok barang',
                'data'            => $data,
                'backgroundColor' => $colors,
                'borderColor'     => $colors,
                'borderWidth'     => 1,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
