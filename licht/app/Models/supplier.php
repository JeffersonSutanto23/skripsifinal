<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Supplier extends Model
{
    protected $fillable = [
    'id',
    'namasupplier',
    'nomorsupplier',
    'alamatsupplier',
    ];
}
