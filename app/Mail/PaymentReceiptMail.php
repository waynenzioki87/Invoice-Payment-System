<?php

namespace App\Mail;

use App\Models\BusinessSetting;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment receipt for invoice ' . $this->payment->invoice->invoice_number
        );
    }

    public function content(): Content
    {
        $this->payment->loadMissing(['invoice.customer', 'invoice.items', 'receivedBy']);

        return new Content(
            view: 'emails.payment-receipt',
            with: ['payment' => $this->payment]
        );
    }

    public function attachments(): array
    {
        $this->payment->loadMissing(['invoice.customer', 'invoice.items', 'receivedBy']);
        $businessSettings = BusinessSetting::first();
        $payment = $this->payment;

        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('payments.receipt', compact('payment', 'businessSettings'))
                    ->setPaper('a4')
                    ->output(),
                'receipt-' . $this->payment->id . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
