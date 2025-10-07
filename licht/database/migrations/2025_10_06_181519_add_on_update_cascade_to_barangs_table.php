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
        Schema::table('barangs', function (Blueprint $table) {
            
            // --- FIX 1: namabarang ---
            // Drop and re-add the foreign key for namabarang
            // Assuming the foreign key name is 'barangmasuks_namabarang_foreign'
            $table->dropForeign('barangs_kategoribarang_foreign'); 
            $table->foreign('kategoribarang')
                  ->references('kategoribarang')
                  ->on('kategoris')
                  ->onUpdate('cascade') 
                  ->onDelete('restrict'); 
    
            $table->dropForeign('barangs_name_foreign'); 
            $table->foreign('name')
          ->references('name')
          ->on('users')
          ->onUpdate('cascade') // <-- FIX for updating user's name
          ->onDelete('restrict'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangmasuks', function (Blueprint $table) {
            
            // --- REVERSE FIX 1: namabarang ---
            $table->dropForeign('barangs_kategoribarang_foreign');
            $table->foreign('kategoribarang')
                  ->references('kategoribarang')
                  ->on('kategoris')
                  ->onDelete('restrict');

            $table->dropForeign('barangs_name_foreign');
            $table->foreign('name')
                  ->references('name')
                  ->on('users')
                  ->onDelete('restrict');
        });
    }
};