<?php

namespace Tests\Feature\Reports;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_summaries_only_count_completed_payments_and_show_balances(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $invoice = $this->createInvoice('Acme Ltd', 'partial', '100.00', now()->subDay()->toDateString());
        $this->createPayment($invoice, '40.00', 'completed', now()->toDateString(), 'cash');
        $this->createPayment($invoice, '20.00', 'pending', now()->toDateString(), 'mpesa');

        $this->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('revenueByDate', fn ($rows): bool => $rows->count() === 1
                && (float) $rows->first()->total === 40.0)
            ->assertViewHas('outstandingInvoices', fn ($rows): bool => $rows->count() === 1
                && (float) $rows->first()->paid_total === 40.0)
            ->assertViewHas('overdueInvoices', fn ($rows): bool => $rows->count() === 1);
    }

    public function test_csv_export_applies_date_filters_and_escapes_formula_cells(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $invoice = $this->createInvoice('=HYPERLINK("https://example.test")', 'unpaid', '50.00');
        $this->createPayment($invoice, '25.00', 'completed', now()->toDateString(), 'cash');

        $response = $this->get(route('reports.export', [
            'type' => 'customers',
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]))->assertOk();

        $this->assertStringContainsString(
            "'=HYPERLINK",
            $response->streamedContent()
        );
    }

    public function test_reports_reject_reversed_date_ranges(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('reports.index', [
                'from' => '2025-02-01',
                'to' => '2025-01-01',
            ]))
            ->assertSessionHasErrors('to');
    }

    private function createInvoice(
        string $customerName,
        string $status,
        string $total,
        ?string $dueDate = null
    ): Invoice {
        $customer = Customer::create([
            'name' => $customerName,
            'email' => 'customer@example.com',
            'phone' => '0712345678',
        ]);

        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'issue_date' => now()->toDateString(),
            'due_date' => $dueDate ?? now()->addDays(7)->toDateString(),
            'subtotal' => $total,
            'tax' => '0.00',
            'total' => $total,
            'currency_code' => 'KES',
            'status' => $status,
        ]);
    }

    private function createPayment(
        Invoice $invoice,
        string $amount,
        string $status,
        string $date,
        string $method
    ): Payment {
        return Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_method' => $method,
            'payment_date' => $date,
            'status' => $status,
        ]);
    }
}
