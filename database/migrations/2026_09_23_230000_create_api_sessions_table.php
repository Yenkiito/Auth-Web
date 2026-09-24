<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('hwid_hash', 64)->nullable()->after('hwid_affected');
        });

        Schema::create('api_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('hwid_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sessions');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('hwid_hash'));
    }
};
