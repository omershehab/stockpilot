<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Mail\CompanyInvitationMail;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CompanyOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_company_is_redirected_to_one_time_setup(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'New Company', onboardingComplete: false);

        $this->actingAs($user)
            ->get(route('companies.dashboard', $company))
            ->assertRedirect(route('companies.onboarding.edit', $company));

        $this->get(route('companies.onboarding.edit', $company))
            ->assertOk()
            ->assertSee('Company currency')
            ->assertSee('Warehouse location')
            ->assertSee('Create my first team')
            ->assertSee('Invite a colleague');
    }

    public function test_owner_can_complete_setup_only_once(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'New Company', onboardingComplete: false);
        $warehouse = $company->locations()->where('is_default', true)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('companies.onboarding.update', $company), [
                'currency' => 'USD',
                'warehouse_name' => 'Riyadh Distribution Center',
                'warehouse_address' => 'Riyadh, Al Sulay District',
            ])
            ->assertRedirect(route('companies.dashboard', $company));

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'currency' => 'USD']);
        $this->assertDatabaseHas('locations', [
            'id' => $warehouse->id,
            'name' => 'Riyadh Distribution Center',
            'address' => 'Riyadh, Al Sulay District',
        ]);
        $this->assertNotNull($company->fresh()->onboarding_completed_at);

        $this->get(route('companies.onboarding.edit', $company))
            ->assertRedirect(route('companies.dashboard', $company));
    }

    public function test_owner_can_create_a_team_and_send_an_invitation_during_setup(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Team Company', onboardingComplete: false);

        $this->actingAs($owner)
            ->patch(route('companies.onboarding.update', $company), [
                'currency' => 'SAR',
                'warehouse_name' => 'Main Warehouse',
                'warehouse_address' => 'Riyadh Industrial Area',
                'team_name' => 'Warehouse Operations',
                'invite_email' => 'operator@example.com',
                'invite_role' => 'staff',
            ])
            ->assertRedirect(route('companies.dashboard', $company))
            ->assertSessionHas('success', 'Company setup completed and the invitation was sent.');

        $team = $company->teams()->where('name', 'Warehouse Operations')->firstOrFail();
        $invitation = CompanyInvitation::where('company_id', $company->id)->firstOrFail();

        $this->assertSame($team->id, $invitation->team_id);
        $this->assertSame('operator@example.com', $invitation->email);
        $this->assertSame('staff', $invitation->role);
        Mail::assertSent(CompanyInvitationMail::class, fn ($mail) => $mail->hasTo('operator@example.com'));
    }
}
