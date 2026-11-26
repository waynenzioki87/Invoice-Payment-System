<?php

namespace Tests\Feature\Payments;

use App\Mail\PaymentReceiptMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_partial_payments_update_invoice_until_balance_is_paid(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $invoice = $this->createInvoice('100.00');

        $this->post(route('payments.store'), $this->paymentData($invoice, '35.00'))
            ->assertRedirect(route('payments.index'));

        Mail::assertQueued(PaymentReceiptMail::class, fn ($mail): bool => $mail->hasTo('customer@example.com'));
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => '35.00',
            'status' => 'completed',
        ]);

        $this->post(route('payments.store'), $this->paymentData($invoice, '65.00'))
            ->assertRedirect(route('payments.index'));

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(2, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_decimal_partial_payments_do_not_leave_a_rounding_balance(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $invoice = $this->createInvoice('0.30');

        $this->post(route('payments.store'), $this->paymentData($invoice, '0.10'));
        $this->post(route('payments.store'), $this->paymentData($invoice, '0.20'));

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertEqualsWithDelta(
            0.30,
            (float) Payment::where('invoice_id', $invoice->id)->sum('amount'),
            0.000001
        );
    }

    public function test_overpayment_is_rejected_without_recording_payment(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $invoice = $this->createInvoice('100.00');

        $this->post(route('payments.store'), $this->paymentData($invoice, '100.01'))
            ->assertSessionHasErrors('amount');

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_invoice_can_open_prefilled_mpesa_payment_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $invoice = $this->createInvoice('100.00');

        $this->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('Initiate payment');

        $this->get(route('payments.create', [
            'invoice_id' => $invoice->id,
            'payment_method' => 'mpesa',
        ]))
            ->assertOk()
            ->assertSee('value="'.$invoice->id.'" selected', false)
            ->assertSee('value="mpesa" selected', false)
            ->assertSee('step="1"', false)
            ->assertSee('min="1"', false)
            ->assertSee('value="100"', false)
            ->assertSee('value="0712345678"', false);
    }

    public function test_payment_amount_must_be_positive_and_have_at_most_two_decimals(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $invoice = $this->createInvoice('100.00');

        foreach (['0', '-1', '1.001'] as $amount) {
            $this->post(route('payments.store'), $this->paymentData($invoice, $amount))
                ->assertSessionHasErrors('amount');
        }

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_customers_cannot_record_payments(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $invoice = $this->createInvoice('100.00');

        $this->post(route('payments.store'), $this->paymentData($invoice, '10.00'))
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_admin_can_download_invoice_and_completed_payment_pdfs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $invoice = $this->createInvoice('100.00');
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => '25.00',
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $this->get(route('invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('payments.receipt', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function createInvoice(string $total): Invoice
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '0712345678',
        ]);

        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'invoice_type' => 'standard',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => $total,
            'tax' => '0.00',
            'total' => $total,
            'currency_code' => 'KES',
            'status' => 'unpaid',
        ]);
    }

    private function paymentData(Invoice $invoice, string $amount): array
    {
        return [
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ];
    }
}
