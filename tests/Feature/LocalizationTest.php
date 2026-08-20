<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_switch_to_arabic_with_rtl_layout(): void
    {
        $this->post(route('locale.update'), ['locale' => 'ar'])
            ->assertRedirect()
            ->assertSessionHas('locale', 'ar');

        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('وضوح كامل للمخزون');
    }

    public function test_authenticated_language_preference_is_saved_to_the_user(): void
    {
        $user = User::factory()->create(['preferred_locale' => 'en']);
        $company = app(CreateCompany::class)->handle($user, 'Localized Company');

        $this->actingAs($user)
            ->from(route('companies.dashboard', $company))
            ->post(route('locale.update'), ['locale' => 'ar'])
            ->assertRedirect(route('companies.dashboard', $company));

        $this->assertSame('ar', $user->fresh()->preferred_locale);

        $this->get(route('companies.dashboard', $company))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('لوحة التحكم');
    }

    public function test_registration_keeps_the_selected_language(): void
    {
        $this->post(route('locale.update'), ['locale' => 'ar']);

        $this->post('/register', [
            'name' => 'Arabic User',
            'company_name' => 'Arabic Company',
            'email' => 'arabic@example.com',
            'mobile_number' => '+966501234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'arabic@example.com',
            'preferred_locale' => 'ar',
        ]);
    }

    public function test_application_top_bars_have_one_language_switcher_without_sidebar_duplicates(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Language Placement Company');

        $clientHtml = $this->actingAs($owner)
            ->get(route('companies.dashboard', $company))
            ->assertOk()
            ->getContent();
        $this->assertSame(1, substr_count($clientHtml, 'action="'.route('locale.update').'"'));

        $owner->forceFill(['is_platform_admin' => true])->save();
        $adminHtml = $this->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();
        $this->assertSame(1, substr_count($adminHtml, 'action="'.route('locale.update').'"'));

        $profileHtml = $this->get(route('profile.edit'))
            ->assertOk()
            ->getContent();
        $this->assertSame(1, substr_count($profileHtml, 'action="'.route('locale.update').'"'));
    }
}
