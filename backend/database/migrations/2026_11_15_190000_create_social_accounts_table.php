<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform');
            $table->string('external_id');
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->text('access_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('connection_status')->default('connected');
            $table->json('metadata')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'platform', 'external_id']);
            $table->index(['tenant_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};