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
        Schema::create('barangmasuks', function (Blueprint $table) {
            $table->id();
            $table->string('namasupplier', length: 255);
            $table->string('namabarang', length: 100);
            $table->integer('jumlahmasuk');         
            $table->double('hargabelieceran');
            $table->double('totalhargabeli');
            $table->enum('keterangan', ['sudah sampai', 'masih dalam proses']);
            $table->datetime('tanggalmasuk');
            $table->string('name');
            $table->timestamps();
            $table->foreign('namabarang')->references('namabarang')->on('barangs');
            $table->foreign('namasupplier')->references('namasupplier')->on('suppliers');
            $table->foreign('name')->references('name')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangmasuks');
    }
};