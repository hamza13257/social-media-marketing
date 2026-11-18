<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->foreignId('week_plan_id')->nullable()->after('brand_id')->constrained('week_plans')->nullOnDelete();
        });

        Schema::table('week_plans', function (Blueprint $table) {
            $table->text('error_message')->nullable()->after('generated_summary');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('week_plan_id');
        });

        Schema::table('week_plans', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};