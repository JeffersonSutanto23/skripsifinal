<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class pelanggan extends Model
{
    protected $fillable = [
    'id',
    'namapelanggan',
    'nomorpelanggan',
    'alamatpelanggan',
    ];
}
