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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $firstBranchId = DB::table('branches')->orderBy('id')->value('id');

        if ($firstBranchId !== null) {
            DB::table('customers')->whereNull('branch_id')->update(['branch_id' => $firstBranchId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
