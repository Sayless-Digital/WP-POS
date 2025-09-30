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
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->string('barcodeable_type', 50);
            $table->unsignedBigInteger('barcodeable_id');
            $table->string('barcode')->unique();
            $table->string('type', 20)->default('EAN13');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->index('barcode');
            $table->index(['barcodeable_type', 'barcodeable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcodes');
    }
};
