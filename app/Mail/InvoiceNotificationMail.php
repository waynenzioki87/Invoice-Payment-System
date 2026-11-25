<?php

namespace App\Mail;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public bool $reminder = false
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->reminder
                ? 'Payment reminder for invoice ' . $this->invoice->invoice_number
                : 'New invoice ' . $this->invoice->invoice_number
        );
    }

    public function content(): Content
    {
        $this->invoice->loadMissing(['customer', 'items', 'payments']);

        return new Content(
            view: 'emails.invoice-notice',
            with: [
                'invoice' => $this->invoice,
                'reminder' => $this->reminder,
            ]
        );
    }

    public function attachments(): array
    {
        $this->invoice->loadMissing(['customer', 'items', 'payments']);
        $businessSettings = BusinessSetting::first();
        $invoice = $this->invoice;

        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('invoices.pdf', compact('invoice', 'businessSettings'))
                    ->setPaper('a4')
                    ->output(),
                'invoice-' . $this->invoice->invoice_number . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
