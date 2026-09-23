<?php

declare(strict_types=1);

namespace App\Domains\Comunicacao\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ComunicacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $tituloAssunto,
        public readonly string $corpo,
        public readonly ?string $nomeClube = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->tituloAssunto);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.comunicacao');
    }
}
