<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('owner_id', 20)->nullable()->unique()->after('project_key');
            $table->string('version', 20)->default('1.0')->after('owner_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->index()->after('last_login_at');
            $table->boolean('hwid_affected')->default(false)->after('expires_at');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->string('mask')->default('KNYR-****-****-****')->after('key');
            $table->string('subscription')->default('default')->after('type');
            $table->text('note')->nullable()->after('subscription');
            $table->string('expiry_unit', 20)->default('days')->after('note');
            $table->unsignedInteger('expiry_duration')->nullable()->after('expiry_unit');
        });

        foreach (DB::table('projects')->whereNull('owner_id')->pluck('id') as $id) {
            DB::table('projects')->where('id', $id)->update(['owner_id' => $this->randomOwnerId()]);
        }
    }

    private function randomOwnerId(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $value = '';
        for ($i = 0; $i < 10; $i++) {
            $value .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $value;
    }

    public function down(): void
    {
        Schema::table('licenses', fn (Blueprint $table) => $table->dropColumn(['mask', 'subscription', 'note', 'expiry_unit', 'expiry_duration']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['expires_at', 'hwid_affected']));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['owner_id', 'version']));
    }
};
