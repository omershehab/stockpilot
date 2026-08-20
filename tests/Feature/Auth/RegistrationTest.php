<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'mobile_number' => '+966 55 123 4567',
            'company_name' => 'Test Company',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('companies', ['name' => 'Test Company', 'onboarding_completed_at' => null]);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'mobile_number' => '+966551234567']);
        $this->assertDatabaseHas('company_user', ['role' => 'owner']);
        $response->assertRedirect(route('companies.onboarding.edit', ['company' => 'test-company'], absolute: false));
    }

    public function test_registration_requires_a_valid_unique_mobile_number(): void
    {
        $this->post('/register', [
            'name' => 'Missing Mobile',
            'email' => 'missing-mobile@example.com',
            'company_name' => 'Missing Mobile Company',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('mobile_number');

        $this->post('/register', [
            'name' => 'Invalid Mobile',
            'email' => 'invalid-mobile@example.com',
            'company_name' => 'Invalid Mobile Company',
            'mobile_number' => 'not-a-number',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('mobile_number');

        $this->assertGuest();
    }
}
