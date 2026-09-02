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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('tax_inclusive')->default(false);
            $table->string('invoice_prefix')->default('INV');
            $table->text('receipt_footer')->nullable();
            $table->boolean('allow_discount')->default(true);
            $table->boolean('allow_negative_stock')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
