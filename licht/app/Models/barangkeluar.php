<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Barang;
use App\Models\Pelanggan;
use App\Models\User;

class BarangKeluar extends Model
{
    protected $table = 'barangkeluars';

    protected $fillable = [
        'namapelanggan',
        'namabarang',
        'jumlahkeluar',
        'hargajualeceran',
        'totalhargajual',
        'keterangan',    
        'tanggalkeluar',
        'name',
    ];

    protected static function booted()
    {
        // CREATE → kurangi stok hanya jika sudah sampai
        static::created(function (self $m) {
            if ($m->keterangan === 'sudah sampai') {
                self::adjustStock($m->namabarang, - (int) $m->jumlahkeluar);
            }
        });

        // UPDATE → batalkan efek lama & terapkan efek baru sesuai status/jumlah/nama
        static::updating(function (self $m) {
            $oldNama   = (string) $m->getOriginal('namabarang');
            $oldJumlah = (int) $m->getOriginal('jumlahkeluar');
            $oldKet    = (string) $m->getOriginal('keterangan');

            $newNama   = (string) $m->namabarang;
            $newJumlah = (int) $m->jumlahkeluar;
            $newKet    = (string) $m->keterangan;

            // Batalkan efek lama jika dulu 'sudah sampai' (keluar → balikin stok)
            if ($oldKet === 'sudah sampai') {
                self::adjustStock($oldNama, +$oldJumlah);
            }

            // Terapkan efek baru jika sekarang 'sudah sampai' (keluar → kurangi stok)
            if ($newKet === 'sudah sampai') {
                self::adjustStock($newNama, -$newJumlah);
            }
        });

        // DELETE → kalau record ini sudah pernah mengurangi stok (sudah sampai), balikin
        static::deleting(function (self $m) {
            if ($m->keterangan === 'sudah sampai') {
                self::adjustStock($m->namabarang, + (int) $m->jumlahkeluar);
            }
        });
    }

    protected static function adjustStock(string $namaBarang, int $delta): void
    {
        DB::transaction(function () use ($namaBarang, $delta) {
            $barang = Barang::where('namabarang', $namaBarang)->lockForUpdate()->first();
            if (! $barang) return;

            $barang->stock = max(0, (int) $barang->stock + $delta);
            $barang->save();
        });
    }

    protected $casts = [
    'tanggalkeluar' => 'datetime', // atau 'date' kalau tipenya DATE
];

}
