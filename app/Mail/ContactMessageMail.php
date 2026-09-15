<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name:string,email:string,topic:string,message:string}  $payload
     */
    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contact form: '.$this->payload['topic'],
            replyTo: [$this->payload['email']],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-message',
            with: [
                'payload' => $this->payload,
            ],
        );
    }
}
