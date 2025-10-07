<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 
use App\Models\barang;
use App\Models\barangmasuk;
use App\Models\supplier;

class BarangMasukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangmasuk = new barangmasuk;
        $barangmasuk->id = "1";
        $barangmasuk->namasupplier = "PT. Harmoni";
        $barangmasuk->namabarang = "Kran 1/2 inch per pcs";
        $barangmasuk->jumlahmasuk = 100;
        $barangmasuk->hargabelieceran = "6000";
        $barangmasuk->totalhargabeli = "600000";
        $barangmasuk->keterangan = "sudah sampai";
        $barangmasuk->tanggalmasuk = "2025-08-01";
        $barangmasuk->name = "superadmin";
        $barangmasuk->save();
    }
}
