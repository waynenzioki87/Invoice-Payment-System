<?php

namespace Tests\Feature\Invoices;

use App\Mail\InvoiceNotificationMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminders_are_queued_at_most_once_per_week_for_outstanding_invoices(): void
    {
        Mail::fake();
        $invoice = $this->createInvoice('unpaid', '100.00', now()->addDay()->toDateString());

        $this->artisan('invoices:send-reminders')
            ->expectsOutput('Queued 1 invoice reminder(s).')
            ->assertExitCode(0);

        $invoice->refresh();
        $this->assertNotNull($invoice->last_reminder_sent_at);
        Mail::assertQueued(
            InvoiceNotificationMail::class,
            fn ($mail): bool => $mail->reminder && $mail->hasTo('customer@example.com')
        );

        $this->artisan('invoices:send-reminders')
            ->expectsOutput('Queued 0 invoice reminder(s).')
            ->assertExitCode(0);
        Mail::assertQueued(InvoiceNotificationMail::class, 1);
    }

    public function test_fully_paid_invoices_are_not_sent_reminders(): void
    {
        Mail::fake();
        $invoice = $this->createInvoice('unpaid', '100.00', now()->subDay()->toDateString());
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => '100.00',
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $this->artisan('invoices:send-reminders')
            ->expectsOutput('Queued 0 invoice reminder(s).')
            ->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    private function createInvoice(string $status, string $total, string $dueDate): Invoice
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '0712345678',
        ]);

        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'issue_date' => now()->toDateString(),
            'due_date' => $dueDate,
            'subtotal' => $total,
            'tax' => '0.00',
            'total' => $total,
            'currency_code' => 'KES',
            'status' => $status,
        ]);
    }
}
