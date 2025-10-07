<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Barang;
use App\Models\Supplier;
use App\Models\User;

class BarangMasuk extends Model
{
    protected $table = 'barangmasuks';

    protected $fillable = [
        'namasupplier',
        'namabarang',
        'jumlahmasuk',
        'hargabelieceran',
        'totalhargabeli',
        'keterangan',      // 'sudah sampai' | 'masih dalam proses'
        'tanggalmasuk',
        'name',
    ];

    

    protected static function booted()
    {
        // CREATE → tambah stok hanya jika sudah sampai
        static::created(function (self $m) {
            if ($m->keterangan === 'sudah sampai') {
                self::adjustStock($m->namabarang, + (int) $m->jumlahmasuk);
            }
        });

        // UPDATE → hitung efek lama & baru, perhatikan perubahan status/jumlah/nama
        static::updating(function (self $m) {
            $oldNama   = (string) $m->getOriginal('namabarang');
            $oldJumlah = (int) $m->getOriginal('jumlahmasuk');
            $oldKet    = (string) $m->getOriginal('keterangan');
            // $hargaeceran    = (string) $m->getOriginal('hargabelieceran');

            $newNama   = (string) $m->namabarang;
            $newJumlah = (int) $m->jumlahmasuk;
            $newKet    = (string) $m->keterangan;
            // $total_harga = $newJumlah * $hargaeceran

            // Batalkan efek lama jika dulu 'sudah sampai'
            if ($oldKet === 'sudah sampai') {
                self::adjustStock($oldNama, -$oldJumlah);
            }

            // Terapkan efek baru jika sekarang 'sudah sampai'
            if ($newKet === 'sudah sampai') {
                self::adjustStock($newNama, +$newJumlah);
            }
        });

        // DELETE → kalau record ini sudah pernah menambah stok (sudah sampai), kembalikan
        static::deleting(function (self $m) {
            if ($m->keterangan === 'sudah sampai') {
                self::adjustStock($m->namabarang, - (int) $m->jumlahmasuk);
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
    'tanggalmasuk' => 'datetime', // atau 'date' kalau tipenya DATE
];

}
