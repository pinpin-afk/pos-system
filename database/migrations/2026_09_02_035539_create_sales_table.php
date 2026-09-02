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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable()->unique();
            $table->foreignId('cashier_id')->constrained('users');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('cost_total', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->string('status');
            $table->timestamp('held_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
