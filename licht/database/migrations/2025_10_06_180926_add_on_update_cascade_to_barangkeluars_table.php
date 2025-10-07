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
        Schema::table('barangkeluars', function (Blueprint $table) {
            
            // --- FIX 1: namapelanggan (FOREIGN KEY) ---
            $table->dropForeign('barangkeluars_namapelanggan_foreign'); 
            $table->foreign('namapelanggan')
                  ->references('namapelanggan')
                  ->on('pelanggans')
                  ->onUpdate('cascade') 
                  ->onDelete('restrict'); 

            // --- FIX 2: namabarang (FOREIGN KEY) ---
            $table->dropForeign('barangkeluars_namabarang_foreign'); 
            $table->foreign('namabarang')
                  ->references('namabarang')
                  ->on('barangs')
                  ->onUpdate('cascade') 
                  ->onDelete('restrict'); 

            // --- FIX 3: hargajualeceran (FOREIGN KEY) ---
            $table->dropForeign('barangkeluars_hargajualeceran_foreign');
            $table->foreign('hargajualeceran')
                  ->references('hargajualeceran') 
                  ->on('barangs')
                  ->onUpdate('cascade') 
                  ->onDelete('restrict'); 
                  
            // --- FIX 4: name (FOREIGN KEY linking to users) ---
            // Note: Assuming 'barangkeluars_name_foreign' is the correct constraint name
            $table->dropForeign('barangkeluars_name_foreign'); 
            $table->foreign('name')
                ->references('name')
                ->on('users')
                ->onUpdate('cascade') // <-- NEW FIX
                ->onDelete('restrict'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangkeluars', function (Blueprint $table) {
            
            // --- REVERSE FIX 1: namapelanggan ---
            $table->dropForeign('barangkeluars_namapelanggan_foreign');
            $table->foreign('namapelanggan')
                  ->references('namapelanggan')
                  ->on('pelanggans')
                  ->onDelete('restrict');

            // --- REVERSE FIX 2: namabarang ---
            $table->dropForeign('barangkeluars_namabarang_foreign');
            $table->foreign('namabarang')
                  ->references('namabarang')
                  ->on('barangs')
                  ->onDelete('restrict');

            // --- REVERSE FIX 3: hargajualeceran ---
            $table->dropForeign('barangkeluars_hargajualeceran_foreign');
            $table->foreign('hargajualeceran')
                  ->references('hargajualeceran')
                  ->on('barangs')
                  ->onDelete('restrict');
                  
            // --- REVERSE FIX 4: name ---
            $table->dropForeign('barangkeluars_name_foreign');
            $table->foreign('name')
                  ->references('name')
                  ->on('users')
                  ->onDelete('restrict');
        });
    }
};