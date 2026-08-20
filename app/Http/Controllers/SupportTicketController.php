<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request, Company $company): View
    {
        $tickets = SupportTicket::query()
            ->where('company_id', $company->id)
            ->where('user_id', $request->user()->id)
            ->withCount('messages')
            ->latest('last_message_at')
            ->paginate(15);

        return view('tickets.index', compact('company', 'tickets'));
    }

    public function create(Company $company): View
    {
        return view('tickets.create', compact('company'));
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = DB::transaction(function () use ($request, $company, $validated) {
            $ticket = SupportTicket::create([
                'company_id' => $company->id,
                'user_id' => $request->user()->id,
                'reference' => 'PENDING-'.str()->uuid(),
                'category' => $validated['category'],
                'subject' => $validated['subject'],
                'status' => 'open',
                'priority' => 'normal',
                'last_message_at' => now(),
            ]);
            $ticket->update(['reference' => 'TKT-'.now()->format('Y').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
            $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'body' => $validated['message'],
                'is_admin' => false,
            ]);

            return $ticket;
        });

        Notification::send(
            User::where('is_platform_admin', true)->get(),
            new SupportTicketActivity(
                'New support ticket',
                ':name opened :reference: :subject',
                ['name' => $request->user()->name, 'reference' => $ticket->reference, 'subject' => $ticket->subject],
                route('admin.tickets.show', $ticket, absolute: false),
                $ticket->reference,
            ),
        );

        return redirect()->route('companies.tickets.show', [$company, $ticket])
            ->with('success', __('Your ticket has been submitted.'));
    }

    public function show(Request $request, Company $company, SupportTicket $ticket): View
    {
        $this->authorizeTicket($request, $company, $ticket);
        $ticket->load(['messages.user', 'assignee']);

        return view('tickets.show', compact('company', 'ticket'));
    }

    public function reply(Request $request, Company $company, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request, $company, $ticket);
        abort_if($ticket->status === 'closed', 422, __('This ticket is closed.'));

        $validated = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        DB::transaction(function () use ($request, $ticket, $validated) {
            $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'body' => $validated['message'],
                'is_admin' => false,
            ]);
            $ticket->update(['status' => 'open', 'last_message_at' => now()]);
        });

        $recipients = $ticket->assignee?->is_platform_admin
            ? collect([$ticket->assignee])
            : User::where('is_platform_admin', true)->get();
        Notification::send(
            $recipients,
            new SupportTicketActivity(
                'Customer replied to a ticket',
                ':name replied to :reference: :subject',
                ['name' => $request->user()->name, 'reference' => $ticket->reference, 'subject' => $ticket->subject],
                route('admin.tickets.show', $ticket, absolute: false),
                $ticket->reference,
            ),
        );

        return back()->with('success', __('Your reply has been added.'));
    }

    private function authorizeTicket(Request $request, Company $company, SupportTicket $ticket): void
    {
        abort_unless($ticket->company_id === $company->id && $ticket->user_id === $request->user()->id, 404);
    }
}
