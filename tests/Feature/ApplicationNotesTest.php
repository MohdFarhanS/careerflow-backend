<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationNotesTest extends TestCase
{
    use RefreshDatabase;

    private function makeApplication(User $user): Application
    {
        return $user->applications()->create([
            'company_name' => 'Acme Corp',
            'position'     => 'Backend Engineer',
            'applied_date' => '2026-01-01',
            'status'       => 'Applied',
        ]);
    }

    public function test_owner_can_update_notes(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);

        $response = $this->actingAs($user)->patchJson("/api/applications/{$application->id}/notes", [
            'notes' => 'Follow up minggu depan.',
        ]);

        $response->assertOk()
            ->assertJson(['notes' => 'Follow up minggu depan.']);

        $this->assertDatabaseHas('applications', [
            'id'    => $application->id,
            'notes' => 'Follow up minggu depan.',
        ]);
    }

    public function test_notes_can_be_cleared_with_null(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);

        $this->actingAs($user)->patchJson("/api/applications/{$application->id}/notes", [
            'notes' => null,
        ])->assertOk();

        $this->assertDatabaseHas('applications', [
            'id'    => $application->id,
            'notes' => null,
        ]);
    }

    public function test_non_owner_cannot_update_notes(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $application = $this->makeApplication($owner);

        $this->actingAs($other)->patchJson("/api/applications/{$application->id}/notes", [
            'notes' => 'percobaan akses ilegal',
        ])->assertForbidden();
    }

    public function test_guest_cannot_update_notes(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);

        $this->patchJson("/api/applications/{$application->id}/notes", [
            'notes' => 'tanpa login',
        ])->assertUnauthorized();
    }
}
