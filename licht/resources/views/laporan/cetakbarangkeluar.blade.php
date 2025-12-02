<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Barang Keluar</title>
    <style>
        :root{ --border:#CBD5E1; --muted:#64748B; --ink:#0F172A; --bg:#ffffff; --accent:#F1F5F9; --card:#F8FAFC; }
        @page { size: A4; margin: 18mm 14mm 16mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: var(--ink); font-size: 12px; background: var(--bg); }
        h2 { margin: 4px 0 8px; text-align:center; letter-spacing:.3px; }
        .meta { display:grid; grid-template-columns: 1fr 1fr; gap: 8px; margin:8px 0 12px; }
        .meta div { background: var(--card); border:1px solid var(--border); padding:8px 10px; border-radius:6px; }
        .stats { display:grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin: 10px 0 12px; }
        .card { background: var(--card); border:1px solid var(--border); padding:10px; border-radius:8px; }
        .card .label { font-size:11px; color:var(--muted); margin-bottom:4px; }
        .card .value { font-size:18px; font-weight:800; }
        table { width:100%; border-collapse: collapse; }
        thead th { background: var(--accent); }
        th, td { border: 1px solid var(--border); padding: 6px 8px; text-align: left; }
        th { font-weight:700; }
        tbody tr:nth-child(odd){ background:#fcfdff; }
        .ok  { color:#065f46; } .bad { color:#b91c1c; }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\DB;

    $dateCol = 'tanggalkeluar';
    $printedAt = now()->locale('id')->translatedFormat('j F Y');

    // Tentukan label periode
    if (!empty($periode ?? null)) {
        $periodeLabel = $periode;
    } else {
        $periodeLabel = '—';
        if (isset($data) && $data instanceof \Illuminate\Support\Collection && $data->isNotEmpty()) {
            $min = Carbon::parse($data->min($dateCol));
            $max = Carbon::parse($data->max($dateCol));
            $periodeLabel = $min->isSameDay($max)
                ? $min->locale('id')->translatedFormat('j F Y')
                : $min->locale('id')->translatedFormat('j M') . ' s/d ' . $max->locale('id')->translatedFormat('j M Y');
        }
    }

    // ============================================================
    // Ambil harga beli TERBARU per barang (bukan rata-rata)
    // ============================================================
    $latestCostMap = DB::table('barangmasuks as bm1')
        ->select('bm1.namabarang', 'bm1.hargabelieceran')
        ->where('bm1.keterangan', '!=', 'masih dalam proses')
        ->whereRaw('bm1.tanggalmasuk = (
            SELECT MAX(bm2.tanggalmasuk)
            FROM barangmasuks bm2
            WHERE bm2.namabarang = bm1.namabarang
              AND bm2.keterangan != "masih dalam proses"
        )')
        ->pluck('hargabelieceran', 'namabarang')
        ->map(fn($v) => (float) $v)
        ->toArray();

    // ============================================================
    // Filter data yang valid (tidak termasuk "masih dalam proses")
    // ============================================================
    $data = $data->filter(fn($r) => strtolower(trim($r->keterangan)) !== 'masih dalam proses');

    // ============================================================
    // Hitung total awal dari data yang sudah difilter
    // ============================================================
    $totalRow        = $data->count() ?? 0;
    $totalQty        = (int) ($data->sum('jumlahkeluar') ?? 0);
    $totalPenjualan  = (int) ($data->sum('totalhargajual') ?? 0);

    $gtModal = 0;
    $gtPenjualan = 0;
    $gtLaba = 0;
@endphp

<div>
    <h2>LAPORAN BARANG KELUAR</h2>
    <p><strong>LICHT Plumbing &amp; Sanitary Ware</strong></p>
    <div class="meta">
        <div><strong>Periode:</strong> {{ $periodeLabel }}</div>
        <div><strong>Tanggal Cetak:</strong> {{ $printedAt }}</div>
    </div>

    <div class="stats">
        <div class="card"><div class="label">Total Data</div><div class="value">{{ number_format($totalRow,0,',','.') }}</div></div>
        <div class="card"><div class="label">Total Qty Keluar</div><div class="value">{{ number_format($totalQty,0,',','.') }}</div></div>
        <div class="card"><div class="label">Total Penjualan</div><div class="value">Rp {{ number_format($totalPenjualan,0,',','.') }}</div></div>
    </div>

    <table>
        <thead>
        <tr>
            <th>Nama Barang</th>
            <th>Pelanggan</th>
            <th style="text-align:right;">Jumlah</th>
            <th style="text-align:right;">Harga Jual</th>
            <th style="text-align:right;">Total Jual</th>
            <th style="text-align:right;">Modal/pcs</th>
            <th style="text-align:right;">Laba/Rugi</th>
            <th>Tanggal</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($data as $r)
            @php
                $qty          = (int) $r->jumlahkeluar;
                $hargaJual    = (int) $r->hargajualeceran;
                $totalJual    = (int) $r->totalhargajual;

                // Ambil harga beli terbaru dari $latestCostMap
                $modalSatuan  = (float) ($latestCostMap[$r->namabarang] ?? 0);
                $modalTotal   = (int) round($modalSatuan * $qty);
                $laba         = $totalJual - $modalTotal;

                $gtModal     += $modalTotal;
                $gtPenjualan += $totalJual;
                $gtLaba      += $laba;
            @endphp
            <tr>
                <td>{{ $r->namabarang }}</td>
                <td>{{ $r->namapelanggan }}</td>
                <td style="text-align:right;">{{ number_format($qty,0,',','.') }}</td>
                <td style="text-align:right;">Rp {{ number_format($hargaJual,0,',','.') }}</td>
                <td style="text-align:right;">Rp {{ number_format($totalJual,0,',','.') }}</td>
                <td style="text-align:right;">Rp {{ number_format($modalSatuan,0,',','.') }}</td>
                <td style="text-align:right;" class="{{ $laba < 0 ? 'bad' : 'ok' }}">
                    Rp {{ number_format($laba,0,',','.') }}
                </td>
                <td>{{ \Illuminate\Support\Carbon::parse($r->tanggalkeluar)->translatedFormat('d/m/y') }}</td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align:center; color:var(--muted);">Tidak ada data</td></tr>
        @endforelse
        </tbody>
    </table>

    @php
        $labelAkhir = $gtLaba > 0 ? 'Untung' : ($gtLaba < 0 ? 'Rugi' : 'Break-even');
    @endphp

    <div style="margin-top:10px;">
        <strong>Total Penjualan Periode Ini:</strong> Rp {{ number_format($gtPenjualan,0,',','.') }}<br>
        <strong>Total Modal Terpakai:</strong> Rp {{ number_format($gtModal,0,',','.') }}<br>
        <strong>Status Periode:</strong> {{ $labelAkhir }} — Rp {{ number_format($gtLaba,0,',','.') }}
    </div>
</div>
</body>
</html>
