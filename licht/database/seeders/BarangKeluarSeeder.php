<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 
use App\Models\barangkeluar;
use App\Models\barang;
use App\Models\pelanggan;

class BarangKeluarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangkeluar = new barangkeluar;
        $barangkeluar->id = "1";
        $barangkeluar->namapelanggan = "Rudi";
        $barangkeluar->namabarang = "Kran 1/2 inch per pcs";
        $barangkeluar->jumlahkeluar = 100;
        $barangkeluar->hargajualeceran = "7000";
        $barangkeluar->totalhargajual = "700000";
        $barangkeluar->keterangan = "sudah sampai";
        $barangkeluar->tanggalkeluar = "2025-08-05";
        $barangkeluar->name = "superadmin";
        $barangkeluar->save();
    }
}
