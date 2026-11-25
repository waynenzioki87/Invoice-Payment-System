<?php

namespace App\Console\Commands;

use App\Mail\InvoiceNotificationMail;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-reminders';

    protected $description = 'Queue reminders for unpaid invoices due soon or overdue';

    public function handle(): int
    {
        $invoices = Invoice::with(['customer', 'items', 'payments'])
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereDate('due_date', '<=', today()->addDays(3))
            ->whereHas('customer', fn ($query) => $query->whereNotNull('email'))
            ->where(function ($query): void {
                $query->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<=', now()->subDays(7));
            })
            ->get();

        $queued = 0;
        foreach ($invoices as $invoice) {
            $paidTotal = $invoice->payments
                ->where('status', 'completed')
                ->sum('amount');

            if ((float) $paidTotal >= (float) $invoice->total) {
                continue;
            }

            Mail::to($invoice->customer->email)
                ->queue(new InvoiceNotificationMail($invoice, reminder: true));

            $invoice->update(['last_reminder_sent_at' => now()]);
            $queued++;
        }

        $this->info("Queued {$queued} invoice reminder(s).");

        return self::SUCCESS;
    }
}
