<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('store_settings')->update([
            'loyalty_earn_points' => 1000,
            'loyalty_spend_amount' => 10000,
            'loyalty_redeem_points' => 1,
            'loyalty_redeem_amount' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('store_settings')->update([
            'loyalty_earn_points' => 1,
            'loyalty_spend_amount' => 10000,
            'loyalty_redeem_points' => 100,
            'loyalty_redeem_amount' => 10000,
        ]);
    }
};
