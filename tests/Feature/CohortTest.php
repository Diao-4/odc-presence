<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Participant;
use App\Models\User;
use App\Services\BadgeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CohortTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Cohort $p11;   // créée par la migration
    private Cohort $p12;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-30 14:05:00'); // mercredi

        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'secret123', 'role' => 'admin', 'is_active' => true]);

        $this->p11 = Cohort::firstOrFail();
        $this->p11->update(['cutoff_time' => '08:00', 'absence_time' => null]);

        $this->actingAs($this->admin)->post(route('cohorts.store'), [
            'program' => 'AWS re/Start', 'name' => 'P12 — Après-midi',
            'cutoff_time' => '14:00', 'absence_time' => '15:00', 'training_days' => [1, 2, 3, 4, 5],
        ])->assertRedirect(route('cohorts.index'));

        $this->p12 = Cohort::where('name', 'P12 — Après-midi')->firstOrFail();
    }

    private function participantIn(Cohort $cohort, string $first): Participant
    {
        $p = Participant::create(['cohort_id' => $cohort->id, 'first_name' => $first, 'last_name' => 'Test', 'is_active' => true]);
        $p->forceFill(['created_at' => '2026-09-01 08:00:00'])->save();

        return $p;
    }

    private function agentOf(Cohort $cohort): User
    {
        return User::create(['name' => 'Agent '.$cohort->name, 'username' => 'agent'.$cohort->id, 'password' => 'secret123', 'role' => 'agent', 'cohort_id' => $cohort->id, 'is_active' => true]);
    }

    public function test_each_cohort_uses_its_own_cutoff_time(): void
    {
        $afternoon = app(BadgeService::class)->issue($this->participantIn($this->p12, 'Awa'));
        $morning   = app(BadgeService::class)->issue($this->participantIn($this->p11, 'Moussa'));

        // 14:05 : 5 min de retard pour P12 (limite 14:00), 6 h 05 pour P11 (limite 08:00)
        $this->postJson(route('scan.store'), ['code' => $afternoon->token])->assertJson(['result' => 'late', 'late_minutes' => 5]);
        $this->postJson(route('scan.store'), ['code' => $morning->token])->assertJson(['result' => 'late', 'late_minutes' => 365]);
    }

    public function test_agent_only_scans_and_sees_their_own_cohort(): void
    {
        $mine   = $this->participantIn($this->p11, 'Fatou');
        $other  = $this->participantIn($this->p12, 'Ibrahim');
        $badge  = app(BadgeService::class)->issue($other);
        $agent  = $this->agentOf($this->p11);

        $this->actingAs($agent)->postJson(route('scan.store'), ['code' => $badge->token])
            ->assertJson(['result' => 'invalid', 'title' => 'Autre cohorte']);
        $this->assertDatabaseCount('attendances', 0);

        $this->actingAs($agent)->get(route('participants.index'))->assertOk()->assertSee('Fatou Test')->assertDontSee('Ibrahim Test');
        $this->actingAs($agent)->get(route('participants.show', $other))->assertNotFound();
        $this->actingAs($agent)->get(route('participants.show', $mine))->assertOk();
    }

    public function test_admin_switches_the_displayed_cohort(): void
    {
        $this->participantIn($this->p11, 'Fatou');
        $this->participantIn($this->p12, 'Ibrahim');

        // La cohorte qui vient d'être créée est affichée
        $this->get(route('participants.index'))->assertSee('Ibrahim Test')->assertDontSee('Fatou Test');

        $this->post(route('cohorts.select', $this->p11), ['back' => route('participants.index')])
            ->assertRedirect(route('participants.index'));

        $this->get(route('participants.index'))->assertSee('Fatou Test')->assertDontSee('Ibrahim Test');
        $this->get(route('dashboard'))->assertOk()->assertSee($this->p11->label);
    }

    public function test_absences_are_counted_with_the_cohort_schedule(): void
    {
        $this->participantIn($this->p11, 'Fatou');   // P11 : absente depuis 08:00
        $this->participantIn($this->p12, 'Ibrahim'); // P12 : pointage ouvert jusqu'à 15:00

        $this->post(route('cohorts.select', $this->p11));
        $rows = $this->get(route('attendances.index'))->assertOk()->viewData('rows');
        $this->assertSame(['Fatou Test'], collect($rows->items())->where('status', 'absent')->pluck('name')->all());

        $this->post(route('cohorts.select', $this->p12));
        $this->assertSame(0, $this->get(route('attendances.index'))->viewData('summary')['absent']);
    }

    public function test_cohort_with_participants_cannot_be_deleted(): void
    {
        $this->participantIn($this->p11, 'Fatou');

        $this->delete(route('cohorts.destroy', $this->p11))->assertSessionHas('error');
        $this->assertModelExists($this->p11);

        $this->delete(route('cohorts.destroy', $this->p12))->assertSessionHas('success');
        $this->assertModelMissing($this->p12);
    }

    public function test_agent_must_belong_to_a_cohort(): void
    {
        $this->post(route('users.store'), [
            'name' => 'Sans cohorte', 'username' => 'sanscohorte', 'role' => 'agent',
            'password' => 'motdepasse1', 'password_confirmation' => 'motdepasse1',
        ])->assertSessionHasErrors('cohort_id');
    }

    public function test_cohort_pages_render(): void
    {
        $this->get(route('cohorts.index'))->assertOk()->assertSee('P12 — Après-midi')->assertSee('14:00');
        $this->get(route('cohorts.create'))->assertOk();
        $this->get(route('users.create', ['cohort_id' => $this->p12->id]))->assertOk()->assertSee("Cohorte de l'agent", false);
    }
}
