<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('week_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('week_start');
            $table->text('brief');
            $table->json('rhythm');
            $table->string('status')->default('draft');
            $table->json('generated_summary')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'brand_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('week_plans');
    }
};