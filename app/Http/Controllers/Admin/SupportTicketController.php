<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status');
        $category = (string) $request->query('category');

        $tickets = SupportTicket::with(['user', 'company', 'assignee'])
            ->withCount('messages')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('company', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when(in_array($status, SupportTicket::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->when(in_array($category, SupportTicket::CATEGORIES, true), fn ($query) => $query->where('category', $category))
            ->latest('last_message_at')
            ->paginate(25)->withQueryString();

        return view('admin.tickets.index', compact('tickets', 'search', 'status', 'category'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'company', 'assignee', 'messages.user']);
        $admins = User::where('is_platform_admin', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.tickets.show', compact('ticket', 'admins'));
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_platform_admin', true)],
        ]);

        $previousStatus = $ticket->status;
        $ticket->update($validated);

        if ($previousStatus !== $ticket->status) {
            $ticket->user->notify(new SupportTicketActivity(
                'Ticket status updated',
                ':reference is now :status.',
                ['reference' => $ticket->reference, 'status' => str($ticket->status)->replace('_', ' ')->title()->toString()],
                $ticket->company
                    ? route('companies.tickets.show', [$ticket->company, $ticket], absolute: false)
                    : route('dashboard', absolute: false),
                $ticket->reference,
            ));
        }

        return back()->with('success', __('Ticket details updated.'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_if($ticket->status === 'closed', 422, __('This ticket is closed.'));
        $validated = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        DB::transaction(function () use ($request, $ticket, $validated) {
            $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'body' => $validated['message'],
                'is_admin' => true,
            ]);
            $ticket->update([
                'status' => 'awaiting_customer',
                'assigned_to' => $ticket->assigned_to ?: $request->user()->id,
                'last_message_at' => now(),
            ]);
        });

        $ticket->user->notify(new SupportTicketActivity(
            'New reply on your ticket',
            'Support replied to :reference: :subject',
            ['reference' => $ticket->reference, 'subject' => $ticket->subject],
            $ticket->company
                ? route('companies.tickets.show', [$ticket->company, $ticket], absolute: false)
                : route('dashboard', absolute: false),
            $ticket->reference,
        ));

        return back()->with('success', __('Reply sent to the customer.'));
    }
}
