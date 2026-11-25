<?php

namespace App\Services;

use App\Models\Invoice;

class InvoicePaymentService
{
    public function refreshStatus(Invoice $invoice): void
    {
        $paidCents = $this->toCents(
            $invoice->payments()
                ->where('status', 'completed')
                ->sum('amount')
        );
        $totalCents = $this->toCents($invoice->total);

        if ($paidCents >= $totalCents) {
            $invoice->update(['status' => 'paid']);

            return;
        }

        if ($paidCents > 0) {
            $invoice->update(['status' => 'partial']);
        }
    }

    public function toCents(string|int|float $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '');

        return ((int) $whole * 100)
            + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
