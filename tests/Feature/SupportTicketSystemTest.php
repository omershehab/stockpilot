<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_can_create_view_and_reply_to_a_ticket(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Support Customer');

        $response = $this->actingAs($user)->post(route('companies.tickets.store', $company), [
            'category' => 'complaint',
            'subject' => 'Scanner keeps disconnecting',
            'message' => 'The scanner disconnects after every stock movement.',
        ]);

        $ticket = SupportTicket::firstOrFail();
        $response->assertRedirect(route('companies.tickets.show', [$company, $ticket]));
        $this->assertMatchesRegularExpression('/^TKT-\d{4}-\d{6}$/', $ticket->reference);
        $this->assertDatabaseHas('support_tickets', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'category' => 'complaint',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'body' => 'The scanner disconnects after every stock movement.',
            'is_admin' => false,
        ]);

        $this->get(route('companies.tickets.index', $company))
            ->assertOk()->assertSee('Scanner keeps disconnecting');
        $this->get(route('companies.tickets.show', [$company, $ticket]))
            ->assertOk()->assertSee('The scanner disconnects after every stock movement.');

        $this->post(route('companies.tickets.replies.store', [$company, $ticket]), [
            'message' => 'This also happens on the products page.',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'body' => 'This also happens on the products page.',
        ]);
    }

    public function test_ticket_validation_rejects_unknown_categories_and_empty_messages(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Validation Customer');

        $this->actingAs($user)->post(route('companies.tickets.store', $company), [
            'category' => 'billing',
            'subject' => '',
            'message' => '',
        ])->assertSessionHasErrors(['category', 'subject', 'message']);

        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_users_cannot_read_or_reply_to_another_users_ticket(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $company = app(CreateCompany::class)->handle($firstUser, 'Private Support');
        $company->users()->attach($secondUser, ['role' => 'viewer', 'status' => 'active']);
        $ticket = $this->createTicket($company->id, $firstUser->id);

        $this->actingAs($secondUser)
            ->get(route('companies.tickets.show', [$company, $ticket]))
            ->assertNotFound();
        $this->actingAs($secondUser)
            ->post(route('companies.tickets.replies.store', [$company, $ticket]), ['message' => 'Not mine'])
            ->assertNotFound();
    }

    public function test_platform_admin_can_filter_manage_and_reply_to_tickets(): void
    {
        $customer = User::factory()->create(['name' => 'Ticket Customer']);
        $company = app(CreateCompany::class)->handle($customer, 'Ticket Company');
        $ticket = $this->createTicket($company->id, $customer->id);
        $admin = User::factory()->create(['name' => 'Support Admin']);
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('admin.tickets.index', ['category' => 'suggestion']))
            ->assertOk()->assertSee($ticket->subject)->assertSee('Ticket Customer');
        $this->get(route('admin.tickets.show', $ticket))
            ->assertOk()->assertSee($ticket->reference)->assertSee('Original support message');

        $this->patch(route('admin.tickets.update', $ticket), [
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $admin->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $admin->id,
        ]);

        $this->post(route('admin.tickets.replies.store', $ticket), [
            'message' => 'Thank you. We have added this to our roadmap.',
        ])->assertRedirect();
        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'body' => 'Thank you. We have added this to our roadmap.',
            'is_admin' => true,
        ]);
        $this->assertSame('awaiting_customer', $ticket->fresh()->status);

        $this->actingAs($customer)->post(route('companies.tickets.replies.store', [$company, $ticket]), [
            'message' => 'Thanks for the update.',
        ])->assertRedirect();
        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_regular_users_cannot_access_the_admin_ticket_queue(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.tickets.index'))->assertForbidden();
    }

    public function test_ticket_activity_creates_working_bell_notifications(): void
    {
        $admin = User::factory()->create(['name' => 'Bell Admin']);
        $admin->forceFill(['is_platform_admin' => true])->save();
        $customer = User::factory()->create(['name' => 'Bell Customer']);
        $company = app(CreateCompany::class)->handle($customer, 'Bell Company');

        $this->actingAs($customer)->post(route('companies.tickets.store', $company), [
            'category' => 'general',
            'subject' => 'Notification test ticket',
            'message' => 'Please notify the administrator.',
        ])->assertRedirect();

        $ticket = SupportTicket::firstOrFail();
        $adminNotification = $admin->notifications()->firstOrFail();
        $this->assertSame('New support ticket', $adminNotification->data['title']);
        $this->assertSame(1, $admin->unreadNotifications()->count());

        $this->actingAs($admin)->get(route('admin.tickets.index'))
            ->assertOk()->assertSee('New support ticket');
        $this->post(route('notifications.read', $adminNotification->id))
            ->assertRedirect(route('admin.tickets.show', $ticket, absolute: false));
        $this->assertNotNull($adminNotification->fresh()->read_at);

        $this->post(route('admin.tickets.replies.store', $ticket), [
            'message' => 'The customer should see this in the bell.',
        ])->assertRedirect();

        $customerNotification = $customer->notifications()->firstOrFail();
        $this->assertSame('New reply on your ticket', $customerNotification->data['title']);
        $this->actingAs($customer)->get(route('companies.tickets.show', [$company, $ticket]))
            ->assertOk()->assertSee('New reply on your ticket');
        $this->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $customer->unreadNotifications()->count());
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $first->notify(new SupportTicketActivity(
            'New support ticket',
            ':name opened :reference: :subject',
            ['name' => 'Someone', 'reference' => 'TKT-1', 'subject' => 'Private'],
            route('dashboard', absolute: false),
            'TKT-1',
        ));

        $notification = $first->notifications()->firstOrFail();
        $this->actingAs($second)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    private function createTicket(int $companyId, int $userId): SupportTicket
    {
        $ticket = SupportTicket::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'reference' => 'TKT-2026-000001',
            'category' => 'suggestion',
            'subject' => 'Please add another report',
            'status' => 'open',
            'priority' => 'normal',
            'last_message_at' => now(),
        ]);
        $ticket->messages()->create([
            'user_id' => $userId,
            'body' => 'Original support message',
            'is_admin' => false,
        ]);

        return $ticket;
    }
}
