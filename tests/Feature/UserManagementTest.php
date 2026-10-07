<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Cohort;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'username' => 'admin', 'password' => 'secret123',
            'role' => User::ROLE_ADMIN, 'is_active' => true,
        ]);
    }

    public function test_admin_creates_an_agent_who_can_log_in(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Moussa Ndiaye', 'username' => 'Moussa', 'role' => 'agent', 'cohort_id' => Cohort::first()->id,
                'password' => 'motdepasse1', 'password_confirmation' => 'motdepasse1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $agent = User::where('username', 'moussa')->firstOrFail();
        $this->assertFalse($agent->isAdmin());

        auth()->logout();
        $this->post(route('login.store'), ['username' => 'moussa', 'password' => 'motdepasse1'])->assertRedirect(route('dashboard'));
    }

    public function test_agents_cannot_create_accounts(): void
    {
        $agent = User::create(['name' => 'Agent', 'username' => 'agent', 'password' => 'secret123', 'role' => 'agent', 'is_active' => true]);

        $this->actingAs($agent)->get(route('users.create'))->assertForbidden();
    }

    public function test_user_list_can_be_filtered_by_role(): void
    {
        User::create(['name' => 'Fatou Agent', 'username' => 'fatou', 'password' => 'secret123', 'role' => 'agent', 'is_active' => true]);

        $this->actingAs($this->admin)->get(route('users.index', ['role' => 'agent']))
            ->assertOk()->assertSee('Fatou Agent')->assertDontSee('>admin<', false);
    }

    public function test_activity_log_can_be_filtered_between_two_dates(): void
    {
        ActivityLog::create(['action' => 'test', 'description' => 'Ancienne opération'])->forceFill(['created_at' => '2026-09-01 10:00:00'])->save();
        ActivityLog::create(['action' => 'test', 'description' => 'Opération récente'])->forceFill(['created_at' => '2026-09-20 10:00:00'])->save();

        $this->actingAs($this->admin)->get(route('logs.index', ['from' => '2026-09-15', 'to' => '2026-09-30']))
            ->assertOk()->assertSee('Opération récente')->assertDontSee('Ancienne opération');
    }
}
