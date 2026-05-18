<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public UserInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Zaproszenie do GallopTrans — ' . $this->invitation->organization->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.team.invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl'  => route('invitations.accept', $this->invitation->token),
            ],
        );
    }
}
