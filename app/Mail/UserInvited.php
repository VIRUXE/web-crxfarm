<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvited extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $signedUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You’ve been invited to CRX Farm',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invited',
        );
    }
}
