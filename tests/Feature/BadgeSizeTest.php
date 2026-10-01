<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeSizeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test',
            'password' => 'secret123', 'role' => User::ROLE_ADMIN, 'is_active' => true,
        ]);
    }

    public function test_badge_can_be_downloaded_in_every_size(): void
    {
        $badge = app(BadgeService::class)->issue(Participant::create(['first_name' => 'Awa', 'last_name' => 'Diop', 'is_active' => true]));

        foreach (array_keys(BadgeService::SIZES) as $size) {
            $response = $this->actingAs($this->admin)->get(route('badges.download', ['badge' => $badge, 'size' => $size]));

            $response->assertOk();
            $this->assertStringStartsWith('%PDF', $response->getContent());
            // Largeur de la page (en points) présente dans le PDF ; la planche est une feuille A4
            $width = isset(BadgeService::SHEETS[$size]) ? '595.28' : number_format(BadgeService::SIZES[$size][1] * 72 / 25.4, 3);
            $this->assertMatchesRegularExpression('/MediaBox \[0\.0+ 0\.0+ '.preg_quote($width, '/').'/', $response->getContent());
        }
    }

    public function test_a4_sheets_hold_nine_or_four_badges_per_page(): void
    {
        foreach (range(1, 10) as $n) {
            app(BadgeService::class)->issue(Participant::create(['first_name' => 'P'.$n, 'last_name' => 'Test', 'is_active' => true]));
        }

        // 10 badges → 2 feuilles de 9, ou 3 feuilles de 4
        foreach (['a4x9' => 2, 'a4x4' => 3] as $size => $pages) {
            $pdf = $this->actingAs($this->admin)->get(route('badges.pdf', ['size' => $size]))->assertOk()->getContent();

            $this->assertSame($pages, preg_match_all('#/Type /Page\b#', $pdf), $size);
            $this->assertMatchesRegularExpression('/MediaBox \[0\.0+ 0\.0+ 595\.28/', $pdf); // A4
        }

        $this->actingAs($this->admin)->get(route('badges.print'))->assertOk()->assertSee('9 par feuille A4');
    }

    public function test_default_size_comes_from_settings_and_invalid_size_falls_back(): void
    {
        $service = app(BadgeService::class);
        $this->assertSame('a4x9', $service->size('nimporte'));
        $this->assertSame('a6', $service->singleSize('a4x9'));

        app(SettingService::class)->set(['badge_size' => 'cr80']);
        $this->assertSame('cr80', $service->size(null));
        $this->assertSame('a5', $service->size('a5'));
    }

    public function test_bulk_downloads_accept_a_size(): void
    {
        app(BadgeService::class)->issue(Participant::create(['first_name' => 'Awa', 'last_name' => 'Diop', 'is_active' => true]));

        $this->actingAs($this->admin)->get(route('badges.pdf', ['size' => 'a7']))->assertOk();
        $this->actingAs($this->admin)->get(route('badges.zip', ['size' => 'cr80']))->assertOk();
        $this->actingAs($this->admin)->get(route('badges.index', ['size' => 'a5']))->assertOk()->assertSee('A5 (148 × 210 mm)');
    }
}
