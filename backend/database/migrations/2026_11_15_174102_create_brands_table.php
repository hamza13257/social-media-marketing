<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();
            $table->json('brand_colors')->nullable();
            $table->text('target_audience')->nullable();
            $table->string('language', 16)->default('en');
            $table->string('tone_of_voice')->nullable();
            $table->json('marketing_objectives')->nullable();
            $table->json('products_services')->nullable();
            $table->json('content_pillars')->nullable();
            $table->text('brand_guidelines')->nullable();
            $table->json('knowledge_documents')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};