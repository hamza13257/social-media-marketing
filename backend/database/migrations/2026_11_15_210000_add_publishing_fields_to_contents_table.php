<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->foreignId('social_account_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
            $table->string('external_post_id')->nullable()->after('status');
            $table->timestamp('published_at')->nullable()->after('external_post_id');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('social_account_id');
            $table->dropColumn(['external_post_id', 'published_at']);
        });
    }
};