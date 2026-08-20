<?php

namespace App\Mail;

use App\Models\CompanyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CompanyInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You are invited to :company on StockPilot', ['company' => $this->invitation->company->name]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.company-invitation');
    }

    public function attachments(): array
    {
        return [];
    }
}
