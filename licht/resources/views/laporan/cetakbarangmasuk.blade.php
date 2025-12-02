<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Barang Masuk</title>
    <style>
        :root{
            --border:#CBD5E1; --muted:#64748B; --ink:#0F172A; --bg:#ffffff;
            --accent:#F1F5F9; --card:#F8FAFC; --warn:#FEE2E2;
        }
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
        tr.proses td { background: var(--warn); color: var(--muted); }
        .totals { margin-top: 10px; font-weight:700; }
        .footer { position: fixed; bottom: -6mm; left: 0; right: 0; text-align: right; font-size: 11px; color:var(--muted);}
        .footer:after { content: "Hal. " counter(page); }
        .sign { margin-top: 18px; display:flex; justify-content: flex-end; }
        .sign .box { text-align:center; width: 220px; }
        .sign .line { margin-top: 54px; border-top:1px solid var(--border); padding-top:4px; }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;

    $dateCol = 'tanggalmasuk';
    $printedAt = now()->locale('id')->translatedFormat('j F Y');

    // Filter hanya data dengan keterangan 'sudah sampai'
    $filtered = $data->filter(function($r) {
        $keterangan = strtolower($r->keterangan ?? '');
        return $keterangan === 'sudah sampai';
    });

    // Tentukan label periode
    if (!empty($periode ?? null)) {
        $periodeLabel = $periode;
    } else {
        $periodeLabel = '—';
        if ($filtered->isNotEmpty()) {
            $min = Carbon::parse($filtered->min($dateCol));
            $max = Carbon::parse($filtered->max($dateCol));
            if ($min->isSameDay($max)) {
                $periodeLabel = $min->locale('id')->translatedFormat('j F Y');
            } elseif ($min->isSameMonth($max)) {
                $periodeLabel = $min->locale('id')->translatedFormat('F Y');
            } elseif ($min->year === $max->year) {
                $periodeLabel = $min->locale('id')->translatedFormat('j M').' s/d '.$max->locale('id')->translatedFormat('j M Y');
            } else {
                $periodeLabel = $min->year.'–'.$max->year;
            }
        }
    }

    // Hitung total dari data 'sudah sampai'
    $totalRow   = $filtered->count();
    $totalQty   = (int) ($filtered->sum('jumlahmasuk') ?? 0);
    $totalModal = (int) ($filtered->sum('totalhargabeli') ?? 0);
@endphp

<div>
    <h2>LAPORAN BARANG MASUK</h2>
    <p><strong>LICHT Plumbing &amp; Sanitary Ware</strong></p>
    <div class="meta">
        <div><strong>Periode:</strong> {{ $periodeLabel }}</div>
        <div><strong>Tanggal Cetak:</strong> {{ $printedAt }}</div>
    </div>

    <div class="stats">
        <div class="card"><div class="label">Total Data Selesai</div><div class="value">{{ $totalRow }}</div></div>
        <div class="card"><div class="label">Total Qty Masuk</div><div class="value">{{ $totalQty }}</div></div>
    </div>

    <table>
        <thead>
        <tr>
            <th>Nama Barang</th>
            <th>Supplier</th>
            <th style="text-align:right;">Jumlah</th>
            <th style="text-align:right;">Harga Beli</th>
            <th style="text-align:right;">Total Beli</th>
            <th>Tanggal</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($data as $r)
            @php 
                $keterangan = strtolower($r->keterangan ?? ''); 
                $isProses = $keterangan === 'masih dalam proses';
            @endphp
            <tr class="{{ $isProses ? 'proses' : '' }}">
                <td>{{ $r->namabarang }}</td>
                <td>{{ $r->namasupplier }}</td>
                <td style="text-align:right;">{{ $r->jumlahmasuk }}</td>
                <td style="text-align:right;">Rp {{ number_format($r->hargabelieceran,0,',','.') }}</td>
                <td style="text-align:right;">Rp {{ number_format($r->totalhargabeli,0,',','.') }}</td>
                <td>{{ \Illuminate\Support\Carbon::parse($r->tanggalmasuk)->translatedFormat('d/m/y') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center; color:var(--muted);">Tidak ada data</td></tr>
        @endforelse
        </tbody>
    </table>

    <p class="totals">Total Modal Periode ini : Rp {{ number_format($totalModal,0,',','.') }}</p>
</div>
</body>
</html>
