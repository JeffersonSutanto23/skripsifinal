<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Kategori;
use App\Models\User;

class Barang extends Model
{
    protected $fillable = [
        'id',
        'namabarang',
        'kategoribarang',
        'hargajualeceran',
        'stock',
        'ketersediaan',
        'name',
    ];
}
