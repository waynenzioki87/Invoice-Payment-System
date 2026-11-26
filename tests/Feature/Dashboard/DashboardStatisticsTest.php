<?php

namespace Tests\Feature\Dashboard;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_uses_completed_payments_for_revenue_and_balance(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $invoice = $this->createInvoice('100.00');
        $this->createPayment($invoice, '35.00', 'completed');
        $this->createPayment($invoice, '20.00', 'pending');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('totalInvoices', 1)
            ->assertViewHas('currencyTotals', fn ($rows): bool => $rows->count() === 1
                && (float) $rows->first()->paid_total === 35.0
                && (float) $rows->first()->outstanding_total === 65.0)
            ->assertViewHas('monthlyInvoices', fn ($rows): bool => $rows->count() === 1
                && (int) $rows->first()->month === now()->month
                && (int) $rows->first()->total === 1);
    }

    public function test_staff_dashboard_counts_owned_work_and_outstanding_invoices(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        $invoice = $this->createInvoice('100.00', $staff->id);
        $this->createPayment($invoice, '25.00', 'completed', $staff->id);

        $this->get(route('staff.dashboard'))
            ->assertOk()
            ->assertViewHas('invoicesCreated', 1)
            ->assertViewHas('paymentsRecorded', 1)
            ->assertViewHas('outstandingInvoices', 1);
    }

    public function test_customer_dashboard_only_aggregates_the_linked_customers_invoices(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '0712345678',
        ]);
        $this->createInvoice('100.00', null, $customer->id);

        $this->actingAs($user)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertViewHas('invoiceCount', 1)
            ->assertViewHas('outstandingByCurrency', fn ($amounts): bool => $amounts->get('KES') === 100.0);
    }

    private function createInvoice(
        string $total,
        ?int $createdBy = null,
        ?int $customerId = null
    ): Invoice {
        if ($customerId === null) {
            $customerId = Customer::create([
                'name' => 'Test Customer',
                'email' => 'customer@example.com',
                'phone' => '0712345678',
            ])->id;
        }

        return Invoice::create([
            'customer_id' => $customerId,
            'created_by' => $createdBy,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => $total,
            'tax' => '0.00',
            'total' => $total,
            'currency_code' => 'KES',
            'status' => 'unpaid',
        ]);
    }

    private function createPayment(
        Invoice $invoice,
        string $amount,
        string $status,
        ?int $receivedBy = null
    ): Payment {
        return Payment::create([
            'invoice_id' => $invoice->id,
            'received_by' => $receivedBy,
            'amount' => $amount,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'status' => $status,
        ]);
    }
}
