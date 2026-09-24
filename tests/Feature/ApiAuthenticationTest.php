<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Device;
use App\Models\Project;
use App\Models\User;
use App\Services\LicenseGeneratorService;
use App\Services\ProjectKeyGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cpp_client_can_initialize_login_activate_license_and_check_session(): void
    {
        $project = $this->project();
        $user = User::factory()->create([
            'project_id' => $project->id,
            'role' => Role::CLIENT,
            'status' => 'active',
            'username' => 'yenko',
            'password' => 'SecurePass!1',
            'expires_at' => now()->addYear(),
            'hwid_affected' => true,
        ]);
        $license = app(LicenseGeneratorService::class)->generate($project, null, 1, [
            'expiry_unit' => 'days',
            'expiry_duration' => 30,
        ])[0];

        $init = $this->postJson('/api/v1/init', [
            'name' => 'CNSI',
            'ownerid' => 'W3CzqUZwRJ',
            'version' => '1.0',
        ])->assertOk()->assertJsonPath('success', true);
        $token = $init->json('session_token');

        $this->withToken($token)->postJson('/api/v1/login', [
            'username' => 'yenko',
            'password' => 'SecurePass!1',
            'hwid' => 'PC-001',
        ])->assertOk()->assertJsonPath('user.username', 'yenko');

        $this->withToken($token)->postJson('/api/v1/license', [
            'license' => $license->key,
            'hwid' => 'PC-001',
            'device_name' => 'Equipo principal',
        ])->assertOk()->assertJsonPath('license.status', 'active');

        $this->withToken($token)->postJson('/api/v1/check', ['hwid' => 'PC-001'])
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('licensed', true);

        $this->assertSame($user->id, $license->fresh()->user_id);
        $this->assertNotNull($license->fresh()->expires_at);
        $this->assertSame(1, Device::where('license_id', $license->id)->count());
    }

    public function test_api_rejects_wrong_version_and_changed_hwid(): void
    {
        $project = $this->project();
        User::factory()->create([
            'project_id' => $project->id,
            'role' => Role::CLIENT,
            'username' => 'yenko',
            'password' => 'SecurePass!1',
            'expires_at' => now()->addYear(),
            'hwid_affected' => true,
        ]);

        $this->postJson('/api/v1/init', [
            'name' => 'CNSI', 'ownerid' => 'W3CzqUZwRJ', 'version' => '2.0',
        ])->assertStatus(426)->assertJsonPath('current_version', '1.0');

        $token = $this->postJson('/api/v1/init', [
            'name' => 'CNSI', 'ownerid' => 'W3CzqUZwRJ', 'version' => '1.0',
        ])->json('session_token');

        $this->withToken($token)->postJson('/api/v1/login', [
            'username' => 'yenko', 'password' => 'SecurePass!1', 'hwid' => 'PC-001',
        ])->assertOk();

        $this->withToken($token)->postJson('/api/v1/check', ['hwid' => 'PC-002'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_license_cannot_be_used_without_user_login(): void
    {
        $project = $this->project();
        $license = app(LicenseGeneratorService::class)->generate($project, null, 1)[0];
        $token = $this->postJson('/api/v1/init', [
            'name' => 'CNSI', 'ownerid' => 'W3CzqUZwRJ', 'version' => '1.0',
        ])->json('session_token');

        $this->withToken($token)->postJson('/api/v1/license', [
            'license' => $license->key, 'hwid' => 'PC-001',
        ])->assertUnauthorized();
    }

    private function project(): Project
    {
        return Project::create([
            'name' => 'CNSI',
            'slug' => 'cnsi',
            'key_prefix' => 'CNSI',
            'description' => null,
            'project_key' => app(ProjectKeyGeneratorService::class)->generate(),
            'owner_id' => 'W3CzqUZwRJ',
            'version' => '1.0',
            'status' => 'active',
        ]);
    }
}
