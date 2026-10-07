<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\BadgeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_pages_render_with_data_and_custom_period(): void
    {
        Carbon::setTestNow('2026-09-30 09:00:00');

        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'secret123', 'role' => 'admin', 'is_active' => true]);
        $p = Participant::create(['first_name' => 'Awa', 'last_name' => 'Diop', 'is_active' => true]);
        $p->forceFill(['created_at' => '2026-09-01 08:00:00'])->save();
        $badge = app(BadgeService::class)->issue($p);
        app(AttendanceService::class)->scan($badge->token, $admin);

        $this->actingAs($admin);
        $range = ['period' => 'custom', 'from' => '2026-09-21', 'to' => '2026-09-30'];

        $this->get(route('attendances.index', $range))->assertOk()->assertSee('Du')->assertSee('Au')->assertSee('Awa Diop')->assertSee('septembre');
        $this->get(route('reports.index', $range))->assertOk()->assertSee('Awa Diop');
        $this->get(route('users.index'))->assertOk()->assertSee('Nouvel agent');
        $this->get(route('users.create'))->assertOk()->assertSee('Nouvel agent');
        $this->get(route('settings.index'))->assertOk();
        $this->get(route('logs.index'))->assertOk();
        $this->get(route('scan'))->assertOk();
    }
}
