<?php

namespace App\Mail;

use App\Models\SecurityEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SecurityEvent $event,
        public ?string $additionalDetails = null
    ) {
    }

    public function envelope(): Envelope
    {
        $severityFa = match ($this->event->severity) {
            'critical' => '🚨 بحرانی',
            'warning'  => '⚠️ هشدار',
            default    => 'ℹ️ اطلاع‌رسانی'
        };

        return new Envelope(
            subject: "[امنیت سرور] {$severityFa}: {$this->event->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.security_alert',
        );
    }
}
