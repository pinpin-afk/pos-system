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
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('loyalty_enabled')->default(true)->after('allow_negative_stock');
            $table->unsignedInteger('loyalty_earn_points')->default(1000)->after('loyalty_enabled');
            $table->decimal('loyalty_spend_amount', 15, 2)->default(10000)->after('loyalty_earn_points');
            $table->unsignedInteger('loyalty_redeem_points')->default(1)->after('loyalty_spend_amount');
            $table->decimal('loyalty_redeem_amount', 15, 2)->default(1)->after('loyalty_redeem_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_enabled',
                'loyalty_earn_points',
                'loyalty_spend_amount',
                'loyalty_redeem_points',
                'loyalty_redeem_amount',
            ]);
        });
    }
};
