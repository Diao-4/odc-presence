<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\Participant;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JustificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Participant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-30 10:30:00'); // mercredi

        $this->admin = User::create([
            'name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test',
            'password' => 'secret123', 'role' => User::ROLE_ADMIN, 'is_active' => true,
        ]);

        $this->participant = Participant::create(['first_name' => 'Awa', 'last_name' => 'Diop', 'is_active' => true]);
        $this->participant->forceFill(['created_at' => '2026-09-01 08:00:00'])->save();

        // Les horaires sont ceux de la cohorte du participant.
        $this->participant->cohort->update(['cutoff_time' => '08:00', 'absence_time' => '10:00', 'training_days' => '1,2,3,4,5']);
    }

    public function test_scan_after_absence_deadline_is_refused(): void
    {
        $badge = app(BadgeService::class)->issue($this->participant);

        $this->actingAs($this->admin)
            ->postJson(route('scan.store'), ['code' => $badge->token])
            ->assertJson(['result' => 'invalid', 'title' => 'Pointage clos']);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_scan_before_absence_deadline_is_recorded_as_late(): void
    {
        Carbon::setTestNow('2026-09-30 09:15:00');
        $badge = app(BadgeService::class)->issue($this->participant);

        $this->actingAs($this->admin)
            ->postJson(route('scan.store'), ['code' => $badge->token])
            ->assertJson(['result' => 'late', 'late_minutes' => 75]);
    }

    public function test_non_scanned_participant_is_absent_only_after_deadline(): void
    {
        $reports  = app(ReportService::class);
        $schedule = $this->participant->schedule();

        Carbon::setTestNow('2026-09-30 09:00:00');
        $this->assertFalse($reports->absenceApplies(today(), $schedule));

        Carbon::setTestNow('2026-09-30 10:01:00');
        $this->assertTrue($reports->absenceApplies(today(), $schedule));
    }

    public function test_admin_can_justify_an_absence_with_a_document(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin)
            ->get(route('justifications.create', ['participant_id' => $this->participant->id, 'date' => '2026-09-29']))
            ->assertOk()
            ->assertSee('Justifier une absence');

        $this->actingAs($this->admin)->post(route('justifications.store'), [
            'participant_id' => $this->participant->id,
            'date'           => '2026-09-29',
            'reason'         => 'Rendez-vous médical',
            'document'       => UploadedFile::fake()->create('certificat.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $j = Justification::firstOrFail();
        $this->assertSame(Justification::ABSENT, $j->type);
        Storage::disk('local')->assertExists($j->document_path);

        $row = $this->rowFor('2026-09-29');
        $this->assertTrue($row['justified']);
        $this->assertSame('Absence justifiée', $row['status_label']);
    }

    public function test_admin_can_justify_a_late_arrival(): void
    {
        Attendance::create([
            'participant_id' => $this->participant->id, 'date' => '2026-09-29',
            'arrived_at' => '2026-09-29 08:20:00', 'status' => Attendance::LATE, 'late_minutes' => 20,
        ]);

        $this->actingAs($this->admin)->post(route('justifications.store'), [
            'participant_id' => $this->participant->id, 'date' => '2026-09-29', 'reason' => 'Embouteillage',
        ])->assertRedirect();

        $this->assertSame(Justification::LATE, Justification::firstOrFail()->type);
        $this->assertSame('Retard justifié', $this->rowFor('2026-09-29')['status_label']);
    }

    public function test_a_presence_cannot_be_justified(): void
    {
        Attendance::create([
            'participant_id' => $this->participant->id, 'date' => '2026-09-29',
            'arrived_at' => '2026-09-29 07:50:00', 'status' => Attendance::PRESENT, 'late_minutes' => 0,
        ]);

        $this->actingAs($this->admin)->post(route('justifications.store'), [
            'participant_id' => $this->participant->id, 'date' => '2026-09-29', 'reason' => 'x',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('justifications', 0);
    }

    public function test_agent_cannot_justify(): void
    {
        $agent = User::create([
            'name' => 'Agent', 'username' => 'agent', 'email' => 'agent@example.test',
            'password' => 'secret123', 'role' => User::ROLE_AGENT, 'is_active' => true,
        ]);

        $this->actingAs($agent)->post(route('justifications.store'), [
            'participant_id' => $this->participant->id, 'date' => '2026-09-29', 'reason' => 'x',
        ])->assertForbidden();
    }

    public function test_pages_show_justification_actions_and_settings(): void
    {
        $this->actingAs($this->admin)->get(route('attendances.index', ['period' => 'yesterday']))
            ->assertOk()->assertSee('Justifier');

        $this->actingAs($this->admin)->get(route('participants.show', $this->participant))
            ->assertOk()->assertSee('Justification');

        $cohort = $this->participant->cohort;

        $this->actingAs($this->admin)->get(route('cohorts.edit', $cohort))
            ->assertOk()->assertSee('Heure limite de pointage');

        $this->actingAs($this->admin)->put(route('cohorts.update', $cohort), [
            'name' => $cohort->name, 'program' => $cohort->program,
            'cutoff_time' => '08:00', 'absence_time' => '07:30', 'training_days' => [1, 2, 3, 4, 5],
        ])->assertSessionHasErrors('absence_time');
    }

    private function rowFor(string $date): array
    {
        $day = Carbon::parse($date);

        return app(ReportService::class)->rows([
            'from' => $day, 'to' => $day, 'participant_id' => $this->participant->id, 'status' => null, 'q' => null,
        ])->first();
    }
}
