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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->decimal('wholesale_price', 15, 2)->nullable()->after('selling_price');
            $table->text('description')->nullable()->after('image');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->decimal('wholesale_price', 15, 2)->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->date('birthday')->nullable()->after('address');
            $table->string('member_number')->nullable()->unique()->after('birthday');
            $table->unsignedInteger('points')->default(0)->after('member_number');
        });

        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('store_name');
            $table->string('timezone', 64)->default('Asia/Jakarta')->after('allow_negative_stock');
            $table->string('currency', 8)->default('IDR')->after('timezone');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('ordered');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('received_quantity', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('completed');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('reason');
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('reason');
            $table->timestamps();
        });

        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('refunded_amount', 15, 2)->default(0)->after('profit');
            $table->foreignId('voided_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by');
            $table->timestamp('voided_at')->nullable()->after('void_reason');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('variant_name')->nullable()->after('product_name');
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('system_quantity', 15, 3);
            $table->decimal('actual_quantity', 15, 3);
            $table->decimal('difference', 15, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn('variant_name');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['refunded_amount', 'void_reason', 'voided_at']);
        });

        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');

        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['logo', 'timezone', 'currency']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['birthday', 'member_number', 'points']);
        });

        Schema::dropIfExists('product_variants');

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn(['wholesale_price', 'description']);
        });

        Schema::dropIfExists('brands');
    }
};
