<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DarkModeDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_authentication_pages_offer_persistent_theme_controls(): void
    {
        foreach ([route('home'), route('login'), route('register')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('stockpilot-theme', false)
                ->assertSee('prefers-color-scheme: dark', false)
                ->assertSee('Toggle color theme');
        }
    }

    public function test_company_workspace_displays_theme_toggle_with_notifications(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Dark Mode Company');

        $this->actingAs($user)
            ->get(route('companies.dashboard', $company))
            ->assertOk()
            ->assertSee('Toggle color theme')
            ->assertSee('Notifications');
    }

    public function test_admin_console_displays_theme_toggle_with_notifications(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Toggle color theme')
            ->assertSee('Notifications');
    }
}
