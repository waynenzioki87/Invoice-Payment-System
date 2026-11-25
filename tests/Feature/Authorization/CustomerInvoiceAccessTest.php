<?php

namespace Tests\Feature\Authorization;

use App\Mail\CustomerWelcomeMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerInvoiceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_another_customers_invoice_or_payment_by_id(): void
    {
        $customerUser = User::factory()->create(['role' => 'customer']);
        $otherUser = User::factory()->create(['role' => 'customer']);
        $customer = $this->createCustomer($customerUser);
        $otherCustomer = $this->createCustomer($otherUser);
        $ownInvoice = $this->createInvoice($customer);
        $otherInvoice = $this->createInvoice($otherCustomer);
        $otherPayment = Payment::create([
            'invoice_id' => $otherInvoice->id,
            'amount' => '10.00',
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $this->actingAs($customerUser)
            ->get(route('customer.invoices.show', $ownInvoice))
            ->assertOk();

        $this->get(route('customer.invoices.show', $otherInvoice))
            ->assertNotFound();

        $this->get(route('customer.invoices.pdf', $otherInvoice))
            ->assertNotFound();

        $this->get(route('customer.payments.show', $otherPayment))
            ->assertNotFound();

        $this->get(route('customer.payments.receipt', $otherPayment))
            ->assertNotFound();
    }

    public function test_customer_registration_links_a_single_matching_business_customer(): void
    {
        Mail::fake();

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => 'existing@example.com',
            'phone' => '0712345678',
        ]);

        $this->post('/register', [
            'name' => 'Existing Customer',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('customer.dashboard'));

        Mail::assertQueued(CustomerWelcomeMail::class, fn ($mail): bool => $mail->hasTo('existing@example.com'));
        $this->assertSame(
            Customer::whereKey($customer->id)->value('user_id'),
            auth()->id()
        );
    }

    private function createCustomer(User $user): Customer
    {
        return Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '0712345678',
        ]);
    }

    private function createInvoice(Customer $customer): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'invoice_type' => 'standard',
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
