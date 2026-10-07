<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-30 07:45:00'); // mercredi, avant l'heure limite

        $this->agent = User::create([
            'name' => 'Agent', 'username' => 'agent', 'password' => 'secret123',
            'role' => User::ROLE_AGENT, 'is_active' => true,
        ]);

        app(SettingService::class)->set(['cutoff_time' => '08:00']);
    }

    private function badge()
    {
        $participant = Participant::create(['first_name' => 'Awa', 'last_name' => 'Diop', 'is_active' => true]);

        return app(BadgeService::class)->issue($participant);
    }

    public function test_qr_code_contains_a_link_to_the_app(): void
    {
        config(['app.url' => 'https://presence.odc.test']);
        $badge = $this->badge();

        $this->assertSame('https://presence.odc.test/b/'.$badge->token, app(BadgeService::class)->scanUrl($badge->token));
    }

    public function test_guest_opening_a_badge_link_must_log_in_then_lands_on_the_scanner(): void
    {
        $badge = $this->badge();
        $link  = route('badge.link', $badge->token);

        $this->get($link)->assertRedirect(route('login'));
        $this->get(route('login'))->assertSee('Badge détecté');

        $this->post(route('login.store'), ['username' => 'agent', 'password' => 'secret123'])
            ->assertRedirect($link);

        $this->get($link)->assertRedirect(route('scan', ['code' => $badge->token]));
        $this->get(route('scan', ['code' => $badge->token]))->assertOk()->assertSee($badge->token);
    }

    public function test_scanning_the_link_records_the_attendance(): void
    {
        $badge = $this->badge();

        $this->actingAs($this->agent)
            ->postJson(route('scan.store'), ['code' => 'https://presence.odc.test/b/'.$badge->token])
            ->assertJson(['result' => 'present']);

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_old_badges_with_the_bare_token_still_work(): void
    {
        $badge = $this->badge();

        $this->actingAs($this->agent)
            ->postJson(route('scan.store'), ['code' => $badge->token])
            ->assertJson(['result' => 'present']);
    }
}
