<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 
use App\Models\barang;
use App\Models\kategori;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barang = new barang;
        $barang->id = "1";
        $barang->namabarang = "Kran 1/2 inch per pcs";
        $barang->kategoribarang = "Kran Air";
        $barang->hargajualeceran = "7000";
        $barang->stock = 120;
        $barang->ketersediaan = "aktif";
        $barang->name = "superadmin";
        $barang->save();

        $barang = new barang;
        $barang->id = "2";
        $barang->namabarang = "Kran 1/2 inch per bks";
        $barang->kategoribarang = "Kran Air";
        $barang->hargajualeceran = "75000";
        $barang->stock = 120;
        $barang->ketersediaan = "aktif";
        $barang->name = "superadmin";
        $barang->save();
    }
}
