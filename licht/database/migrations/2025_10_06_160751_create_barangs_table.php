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
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('namabarang', 100);
            $table->string('kategoribarang', 50);
            $table->double('hargajualeceran');
            $table->integer('stock');
            $table->enum('ketersediaan', ['aktif', 'tidak aktif']);
            $table->string('name');
            $table->timestamps();
            $table->unique('namabarang');
            $table->unique('hargajualeceran');
            $table->foreign('kategoribarang')
            ->references('kategoribarang')
            ->on('kategoris')
            ->onUpdate('cascade') 
            ->onDelete('restrict');
            $table->foreign('name')->references('name')->on('users')->onUpdate('cascade') 
            ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};