<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Jobs\GenerateLicenseBatch;
use App\Models\ActivityLog;
use App\Models\License;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use App\Services\LicenseGeneratorService;
use App\Services\PartnerHierarchyService;
use App\Services\ProjectKeyGeneratorService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KenyraLicenseTest extends TestCase
{
    use RefreshDatabase;

    private function user(Role $role, array $attributes = []): User
    {
        return User::factory()->create([...$attributes, 'role' => $role]);
    }

    private function project(string $slug = 'project-a'): Project
    {
        return Project::create(['name' => $slug, 'slug' => $slug, 'description' => null, 'project_key' => app(ProjectKeyGeneratorService::class)->generate(), 'status' => 'active']);
    }

    private function partner(Project $project, ?Partner $parent = null): Partner
    {
        $account = $this->user(Role::PARTNER, ['project_id' => $project->id, 'partner_id' => null]);
        $partner = Partner::create(['project_id' => $project->id, 'parent_id' => $parent?->id, 'user_id' => $account->id, 'name' => $account->name, 'status' => 'active']);
        $account->update(['partner_id' => $partner->id]);

        return $partner;
    }

    public function test_each_role_is_redirected_to_its_dashboard(): void
    {
        foreach ([Role::OWNER, Role::ADMIN, Role::CLIENT] as $role) {
            $user = $this->user($role);
            $this->post('/login', ['login' => $user->username, 'password' => 'password'])->assertRedirect(route($role->dashboard()));
            $this->post('/logout');
        }
        $project = $this->project();
        $partner = $this->partner($project);
        $user = $partner->account;
        $this->post('/login', ['login' => $user->username, 'password' => 'password'])->assertRedirect(route('partner.dashboard'));
    }

    public function test_blocked_account_cannot_login(): void
    {
        $user = $this->user(Role::CLIENT, ['status' => 'blocked']);
        $this->post('/login', ['login' => $user->username, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_owner_can_create_project_with_unique_random_key_and_rotate_it(): void
    {
        $owner = $this->user(Role::OWNER);
        $this->actingAs($owner)->post(route('admin.projects.store'), ['name' => 'Tool', 'key_prefix' => 'TOOL', 'description' => '', 'status' => 'active'])->assertSessionHasNoErrors();
        $project = Project::firstOrFail();
        $old = $project->project_key;
        $this->assertMatchesRegularExpression('/^KNYR-PROJ-[A-F0-9]{12}$/', $old);
        $this->actingAs($owner)->withSession(['auth.password_confirmed_at' => time()])->post(route('admin.projects.rotate', $project))->assertSessionHasNoErrors();
        $this->assertNotSame($old, $project->fresh()->project_key);
    }

    public function test_partner_hierarchy_has_unlimited_depth_and_rejects_cycles(): void
    {
        $project = $this->project();
        $a = $this->partner($project);
        $b = $this->partner($project, $a);
        $c = $this->partner($project, $b);
        $d = $this->partner($project, $c);
        $service = app(PartnerHierarchyService::class);
        $this->assertSame([$a->id, $b->id, $c->id, $d->id], $service->descendantIds($a));
        $this->expectException(ValidationException::class);
        $service->assertValidParent($a, $d);
    }

    public function test_partner_cannot_see_another_project_or_tree(): void
    {
        $a = $this->project('a');
        $b = $this->project('b');
        $partnerA = $this->partner($a);
        $partnerB = $this->partner($b);
        $license = app(LicenseGeneratorService::class)->generate($b, $partnerB, 1)[0];
        $this->actingAs($partnerA->account)->put(route('partner.licenses.update', $license), ['status' => 'suspended', 'expires_at' => null, 'max_devices' => 1])->assertForbidden();
    }

    public function test_partner_can_create_only_a_child_in_own_project(): void
    {
        $project = $this->project();
        $parent = $this->partner($project);
        $this->actingAs($parent->account)->post(route('partner.partners.store'), ['name' => 'Child', 'username' => 'child', 'email' => 'child@example.test', 'password' => 'SecurePass!1', 'password_confirmation' => 'SecurePass!1', 'status' => 'active'])->assertSessionHasNoErrors();
        $child = Partner::where('name', 'Child')->firstOrFail();
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame($project->id, $child->project_id);
    }

    public function test_license_generator_creates_secure_unique_serials_in_bulk(): void
    {
        $project = $this->project();
        $licenses = app(LicenseGeneratorService::class)->generate($project, null, 100, ['max_devices' => 5]);
        $this->assertCount(100, $licenses);
        $this->assertCount(100, array_unique(array_map(fn ($l) => $l->key, $licenses)));
        $this->assertMatchesRegularExpression('/^KNYR-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $licenses[0]->key);
        $this->assertSame(5, $licenses[0]->max_devices);
    }

    public function test_lifetime_license_has_no_expiration(): void
    {
        $license = app(LicenseGeneratorService::class)->generate($this->project(), null, 1, ['type' => 'lifetime', 'expires_at' => null])[0];
        $this->assertNull($license->expires_at);
    }

    public function test_large_bulk_generation_is_chunked_into_queue_jobs(): void
    {
        Queue::fake();
        $owner = $this->user(Role::OWNER);
        $project = $this->project();
        $this->actingAs($owner)->post(route('admin.licenses.bulk'), [
            'project_id' => $project->id, 'quantity' => 2501, 'license_mask' => 'KNYR-****-****-****',
            'subscription' => 'default', 'expiry_unit' => 'days', 'expiry_duration' => 30,
            'uppercase' => true, 'lowercase' => false, 'max_devices' => 1,
        ])->assertSessionHasNoErrors();
        Queue::assertPushed(GenerateLicenseBatch::class, 3);
    }

    public function test_expiration_command_marks_only_due_non_revoked_licenses(): void
    {
        $project = $this->project();
        $due = app(LicenseGeneratorService::class)->generate($project, null, 1, ['status' => 'active', 'expires_at' => now()->subMinute()])[0];
        $revoked = app(LicenseGeneratorService::class)->generate($project, null, 1, ['status' => 'revoked', 'expires_at' => now()->subMinute()])[0];
        $this->artisan('kenyra:expire-licenses')->assertSuccessful();
        $this->assertSame('expired', $due->fresh()->status);
        $this->assertSame('revoked', $revoked->fresh()->status);
    }

    public function test_database_rejects_duplicate_license_keys(): void
    {
        $project = $this->project();
        $license = app(LicenseGeneratorService::class)->generate($project, null, 1)[0];
        $this->expectException(QueryException::class);
        License::create(['project_id' => $project->id, 'key' => $license->key, 'type' => 'standard', 'status' => 'available', 'max_devices' => 1]);
    }

    public function test_device_registration_enforces_license_owner_and_max_devices(): void
    {
        $project = $this->project();
        $partner = $this->partner($project);
        $client = $this->user(Role::CLIENT, ['project_id' => $project->id, 'partner_id' => $partner->id]);
        $license = app(LicenseGeneratorService::class)->generate($project, $partner, 1, ['user_id' => $client->id, 'max_devices' => 1])[0];
        $payload = ['license_id' => $license->id, 'user_id' => $client->id, 'hwid' => 'HWID-ONE', 'device_name' => 'PC', 'status' => 'active'];
        $this->actingAs($partner->account)->post(route('partner.devices.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($partner->account)->post(route('partner.devices.store'), [...$payload, 'hwid' => 'HWID-TWO'])->assertStatus(422);
        $this->assertDatabaseCount('devices', 1);
    }

    public function test_revoked_license_cannot_be_reactivated(): void
    {
        $owner = $this->user(Role::OWNER);
        $license = app(LicenseGeneratorService::class)->generate($this->project(), null, 1, ['status' => 'revoked'])[0];
        $this->actingAs($owner)->put(route('admin.licenses.update', $license), ['status' => 'active', 'expires_at' => null, 'max_devices' => 1])->assertSessionHasErrors('status');
        $this->assertSame('revoked', $license->fresh()->status);
    }

    public function test_actions_are_logged_without_secrets(): void
    {
        $owner = $this->user(Role::OWNER);
        $this->actingAs($owner)->post(route('admin.projects.store'), ['name' => 'Logged', 'key_prefix' => 'LOG', 'description' => null, 'status' => 'active']);
        $log = ActivityLog::where('action', 'project.created')->firstOrFail();
        $this->assertArrayNotHasKey('project_key', $log->metadata ?? []);
        $this->assertSame($owner->username, $log->actor);
    }

    public function test_client_cannot_enter_administration(): void
    {
        $this->actingAs($this->user(Role::CLIENT))->get(route('admin.dashboard'))->assertForbidden();
    }
}
