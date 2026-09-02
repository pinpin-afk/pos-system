<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('plan')->default('starter');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();
        $tenantId = DB::table('tenants')->where('slug', 'toko-maju-jaya')->value('id');

        if ($tenantId === null) {
            $tenantId = DB::table('tenants')->insertGetId([
                'name' => 'Toko Maju Jaya',
                'slug' => 'toko-maju-jaya',
                'plan' => 'unlimited',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $branchId = DB::table('branches')->where('tenant_id', $tenantId)->orderBy('id')->value('id');

        if ($branchId === null) {
            $branchId = DB::table('branches')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => 'Cabang Pusat',
                'code' => 'PST',
                'address' => 'Jakarta',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $warehouseId = DB::table('warehouses')->where('branch_id', $branchId)->orderBy('id')->value('id');

        if ($warehouseId === null) {
            $warehouseId = DB::table('warehouses')->insertGetId([
                'branch_id' => $branchId,
                'name' => 'Gudang Utama',
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
                $table->string('pin')->nullable()->after('password');
                $table->string('card_number')->nullable()->unique()->after('pin');
            });
        }

        if (! Schema::hasColumn('store_settings', 'tenant_id')) {
            Schema::table('store_settings', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('stocks', 'warehouse_id')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            });
        }

        DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $tenantId, 'branch_id' => $branchId]);
        DB::table('store_settings')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        DB::table('stocks')->whereNull('warehouse_id')->update(['warehouse_id' => $warehouseId]);

        $stockIndexes = collect(Schema::getIndexes('stocks'));
        $stockForeigns = collect(Schema::getForeignKeys('stocks'));
        $hasComposite = $stockIndexes->contains(fn (array $index): bool => in_array('product_id', $index['columns'], true)
            && in_array('warehouse_id', $index['columns'], true)
            && ($index['unique'] ?? false));
        $hasProductUnique = $stockIndexes->contains(fn (array $index): bool => ($index['name'] ?? '') === 'stocks_product_id_unique');
        $hasProductForeign = $stockForeigns->contains(fn (array $foreign): bool => in_array('product_id', $foreign['columns'], true));

        if ($hasProductForeign) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        }

        if ($hasProductUnique) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropUnique(['product_id']);
            });
        }

        if (! $hasProductForeign) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            });
        }

        if (! $hasComposite) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->unique(['product_id', 'warehouse_id']);
            });
        }

        if (! Schema::hasColumn('sales', 'branch_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('cashier_shift_id')->constrained()->nullOnDelete();
                $table->foreignId('warehouse_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
                $table->unsignedInteger('points_redeemed')->default(0)->after('refunded_amount');
                $table->string('voucher_code')->nullable()->after('points_redeemed');
            });
        }

        if (! Schema::hasColumn('cashier_shifts', 'branch_id')) {
            Schema::table('cashier_shifts', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('purchases', 'warehouse_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreignId('warehouse_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('payments', 'label')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('label')->nullable()->after('method');
            });
        }

        if (! Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('number')->unique();
                $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_transfer_items')) {
            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->decimal('quantity', 15, 3);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->string('category');
                $table->decimal('amount', 15, 2);
                $table->date('spent_on');
                $table->string('description')->nullable();
                $table->string('attachment')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type');
                $table->decimal('value', 15, 2)->default(0);
                $table->unsignedInteger('buy_qty')->nullable();
                $table->unsignedInteger('get_qty')->nullable();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->time('starts_at')->nullable();
                $table->time('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('discount_type');
                $table->decimal('discount_value', 15, 2);
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('properties')->nullable();
                $table->timestamps();
                $table->index(['subject_type', 'subject_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');

        if (Schema::hasColumn('payments', 'label')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('label');
            });
        }

        if (Schema::hasColumn('purchases', 'warehouse_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }

        if (Schema::hasColumn('cashier_shifts', 'branch_id')) {
            Schema::table('cashier_shifts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        if (Schema::hasColumn('sales', 'branch_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
                $table->dropConstrainedForeignId('warehouse_id');
                $table->dropColumn(['points_redeemed', 'voucher_code']);
            });
        }

        if (Schema::hasColumn('stocks', 'warehouse_id')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropUnique(['product_id', 'warehouse_id']);
                $table->dropConstrainedForeignId('warehouse_id');
                $table->unique('product_id');
            });
        }

        if (Schema::hasColumn('store_settings', 'tenant_id')) {
            Schema::table('store_settings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }

        if (Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
                $table->dropConstrainedForeignId('branch_id');
                $table->dropColumn(['pin', 'card_number']);
            });
        }

        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('tenants');
    }
};
