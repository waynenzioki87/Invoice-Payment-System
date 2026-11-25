<?php

namespace Tests\Feature\Invoices;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_creates_an_unpaid_copy_without_payment_records(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $invoice = $this->createInvoice();
        $invoice->items()->create([
            'description' => 'Consulting',
            'quantity' => 2,
            'unit_price' => '50.00',
            'amount' => '100.00',
        ]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => '25.00',
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $this->post(route('invoices.duplicate', $invoice))
            ->assertRedirect();

        $copy = Invoice::where('id', '!=', $invoice->id)->firstOrFail();
        $this->assertSame('unpaid', $copy->status);
        $this->assertNotSame($invoice->invoice_number, $copy->invoice_number);
        $this->assertSame(1, $copy->items()->count());
        $this->assertSame(0, $copy->payments()->count());
    }

    public function test_invoice_with_payments_cannot_be_deleted_or_change_currency(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $invoice = $this->createInvoice();
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => '25.00',
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $this->put(route('invoices.update', $invoice), [
            'customer_id' => $invoice->customer_id,
            'issue_date' => $invoice->issue_date->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'currency_code' => 'USD',
        ])->assertSessionHasErrors('currency_code');

        $this->delete(route('invoices.destroy', $invoice))
            ->assertSessionHasErrors('invoice');
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    private function createInvoice(): Invoice
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
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => '100.00',
            'tax' => '0.00',
            'total' => '100.00',
            'currency_code' => 'KES',
            'status' => 'unpaid',
        ]);
    }
}
