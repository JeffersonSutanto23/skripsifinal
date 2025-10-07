<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::create('pelanggans', function (Blueprint $table) {
            $table->id();
            $table->string('namapelanggan', 255);
            $table->string('nomorpelanggan', 20);
            $table->text('alamatpelanggan');
            $table->timestamps();
            $table->unique('namapelanggan'); 
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
    }
};