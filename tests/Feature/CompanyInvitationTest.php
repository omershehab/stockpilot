<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invited_new_user_can_create_an_account_and_join_the_company_team(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Inviting Company');
        $team = $company->teams()->create(['name' => 'Operations', 'color' => 'violet', 'permissions' => []]);
        $invitation = $company->invitations()->create([
            'team_id' => $team->id,
            'invited_by_user_id' => $owner->id,
            'email' => 'new.member@example.com',
            'role' => 'staff',
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        $this->get(route('invitations.show', $invitation))
            ->assertOk()
            ->assertSee('Join Inviting Company')
            ->assertSee('new.member@example.com');

        $this->post(route('invitations.accept', $invitation), [
            'name' => 'New Member',
            'mobile_number' => '+966 54 555 1234',
            'password' => 'Secure-password-123',
            'password_confirmation' => 'Secure-password-123',
        ])->assertRedirect(route('companies.dashboard', $company));

        $user = User::where('email', 'new.member@example.com')->firstOrFail();
        $this->assertSame('+966545551234', $user->mobile_number);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'role' => 'staff',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $user->id]);
        $this->assertNotNull($invitation->fresh()->accepted_at);

        $this->get(route('invitations.show', $invitation))->assertStatus(410);
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Expired Company');
        $invitation = CompanyInvitation::create([
            'company_id' => $company->id,
            'invited_by_user_id' => $owner->id,
            'email' => 'late@example.com',
            'role' => 'viewer',
            'token' => Str::random(64),
            'expires_at' => now()->subMinute(),
        ]);

        $this->post(route('invitations.accept', $invitation), [
            'name' => 'Late User',
            'password' => 'Secure-password-123',
            'password_confirmation' => 'Secure-password-123',
        ])->assertStatus(410);

        $this->assertDatabaseMissing('users', ['email' => 'late@example.com']);
    }
}
