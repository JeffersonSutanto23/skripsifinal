<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 
use App\Models\supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supplier = new supplier;
        $supplier->id = "1";
        $supplier->namasupplier = "PT. Harmoni";
        $supplier->nomorsupplier = "08138877484";
        $supplier->alamatsupplier = "Jl. Perak No. 80";
        $supplier->save();
    }
}
