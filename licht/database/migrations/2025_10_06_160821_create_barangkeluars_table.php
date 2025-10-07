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
        Schema::create('barangkeluars', function (Blueprint $table) {
            $table->id();
            $table->string('namapelanggan', length: 255);
            $table->string('namabarang', length: 100);
            $table->integer('jumlahkeluar');         
            $table->double('hargajualeceran');
            $table->double('totalhargajual');
            $table->enum('keterangan', ['sudah sampai', 'masih dalam proses']);
            $table->datetime('tanggalkeluar');
            $table->string('name');
            $table->timestamps();
            $table->foreign('namabarang')->references('namabarang')->on('barangs');
            $table->foreign('namapelanggan')->references('namapelanggan')->on('pelanggans');
            $table->foreign('hargajualeceran')->references('hargajualeceran')->on('barangs');
            $table->foreign('name')->references('name')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangkeluars');
    }
};