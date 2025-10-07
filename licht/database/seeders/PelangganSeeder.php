<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 
use App\Models\pelanggan;

class PelangganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pelanggan = new pelanggan;
        $pelanggan->id = "1";
        $pelanggan->namapelanggan = "Rudi";
        $pelanggan->nomorpelanggan = "08137521654";
        $pelanggan->alamatpelanggan = "Jl. Tikus No. 8";
        $pelanggan->save();
    }
}
