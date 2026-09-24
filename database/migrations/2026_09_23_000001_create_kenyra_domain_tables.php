<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('partners')->restrictOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'parent_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
        });

        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key')->unique();
            $table->string('type', 20)->default('standard');
            $table->string('status', 20)->default('available')->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('max_devices')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'partner_id', 'status']);
            $table->index(['project_id', 'user_id']);
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->string('hwid');
            $table->string('device_name')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->unique(['license_id', 'hwid']);
            $table->index(['project_id', 'user_id']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['project_id', 'created_at']);
            $table->index(['partner_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('licenses');
        Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['partner_id']));
        Schema::dropIfExists('partners');
    }
};
