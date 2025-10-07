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
        Schema::table('barangmasuks', function (Blueprint $table) {
            
            // --- FIX 1: namabarang ---
            // Drop and re-add the foreign key for namabarang
            // Assuming the foreign key name is 'barangmasuks_namabarang_foreign'
            $table->dropForeign('barangmasuks_namabarang_foreign'); 
            $table->foreign('namabarang')
                  ->references('namabarang')
                  ->on('barangs')
                  ->onUpdate('cascade') 
                  ->onDelete('restrict'); 

            // --- FIX 2: namasupplier ---
            // Drop and re-add the foreign key for namasupplier
            // Assuming the foreign key name is 'barangmasuks_namasupplier_foreign'
            $table->dropForeign('barangmasuks_namasupplier_foreign'); 
            $table->foreign('namasupplier')
                  ->references('namasupplier')
                  ->on('suppliers')
                  ->onUpdate('cascade') 
                  ->onDelete('restrict'); 
    
            $table->dropForeign('barangmasuks_name_foreign'); 
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
            $table->dropForeign('barangmasuks_namabarang_foreign');
            $table->foreign('namabarang')
                  ->references('namabarang')
                  ->on('barangs')
                  ->onDelete('restrict');

            // --- REVERSE FIX 2: namasupplier ---
            $table->dropForeign('barangmasuks_namasupplier_foreign');
            $table->foreign('namasupplier')
                  ->references('namasupplier')
                  ->on('suppliers')
                  ->onDelete('restrict');

            $table->dropForeign('barangmasuks_name_foreign');
            $table->foreign('name')
                  ->references('name')
                  ->on('users')
                  ->onDelete('restrict');
        });
    }
};