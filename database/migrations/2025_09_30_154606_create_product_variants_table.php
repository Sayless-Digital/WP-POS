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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('name');
            $table->string('sku', 100)->unique();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->string('option1_name', 50)->nullable();
            $table->string('option1_value', 100)->nullable();
            $table->string('option2_name', 50)->nullable();
            $table->string('option2_value', 100)->nullable();
            $table->string('option3_name', 50)->nullable();
            $table->string('option3_value', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('image_url', 500)->nullable();
            $table->unsignedBigInteger('woocommerce_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('sku');
            $table->index('woocommerce_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
