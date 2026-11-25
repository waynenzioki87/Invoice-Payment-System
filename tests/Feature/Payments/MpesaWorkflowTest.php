<?php

namespace Tests\Feature\Payments;

use App\Mail\PaymentReceiptMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MpesaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        config([
            'services.mpesa.base_url' => 'https://sandbox.safaricom.co.ke',
            'services.mpesa.consumer_key' => 'test-key',
            'services.mpesa.consumer_secret' => 'test-secret',
            'services.mpesa.shortcode' => '174379',
            'services.mpesa.passkey' => 'test-passkey',
            'services.mpesa.callback_url' => 'https://example.test/mpesa/callback',
            'services.mpesa.transaction_type' => 'CustomerPayBillOnline',
            'services.mpesa.account_reference_prefix' => 'INVOICEX',
        ]);
    }

    public function test_stk_push_callback_confirms_payment_and_updates_invoice(): void
    {
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'sandbox-token',
                'expires_in' => '3599',
            ]),
            'sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0',
                'CheckoutRequestID' => 'ws_CO_test_001',
                'MerchantRequestID' => 'merchant_test_001',
            ]),
            'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([
                'ResponseCode' => '0',
                'ResultCode' => '0',
            ]),
        ]);

        $staff = User::factory()->create(['role' => 'staff']);
        $invoice = $this->createInvoice('100.00');

        $response = $this->actingAs($staff)->post(route('payments.mpesa.initiate'), [
            'invoice_id' => $invoice->id,
            'amount' => '60',
            'phone_number' => '0712345678',
        ]);

        $payment = Payment::where('checkout_request_id', 'ws_CO_test_001')->firstOrFail();
        $response->assertRedirect(route('payments.show', $payment));
        $this->assertSame('pending', $payment->status);
        $this->assertSame('254712345678', $payment->phone_number);

        Http::assertSent(function ($request): bool {
            return str_ends_with($request->url(), '/mpesa/stkpush/v1/processrequest')
                && $request['PhoneNumber'] === '254712345678'
                && $request['Amount'] === 60
                && $request['Password'] === base64_encode(
                    '174379test-passkey'.$request['Timestamp']
                );
        });

        $this->postJson(route('mpesa.callback'), $this->successCallback('ws_CO_test_001', 60))
            ->assertOk();

        Mail::assertQueued(
            PaymentReceiptMail::class,
            fn ($mail): bool => $mail->hasTo('customer@example.com')
        );
        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertSame('RCP12345678', $payment->fresh()->transaction_reference);
        $this->assertSame('partial', $invoice->fresh()->status);

        $this->postJson(route('mpesa.callback'), $this->successCallback('ws_CO_test_001', 60))
            ->assertOk();
        $this->assertSame(1, Payment::where('transaction_reference', 'RCP12345678')->count());
    }

    public function test_initiation_reserves_balance_to_prevent_overlapping_stk_requests(): void
    {
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'sandbox-token',
                'expires_in' => '3599',
            ]),
            'sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0',
                'CheckoutRequestID' => 'ws_CO_test_002',
                'MerchantRequestID' => 'merchant_test_002',
            ]),
        ]);

        $staff = User::factory()->create(['role' => 'staff']);
        $invoice = $this->createInvoice('100.00');
        $this->actingAs($staff);

        $this->post(route('payments.mpesa.initiate'), [
            'invoice_id' => $invoice->id,
            'amount' => '60',
            'phone_number' => '0712345678',
        ])->assertRedirect();

        $this->post(route('payments.mpesa.initiate'), [
            'invoice_id' => $invoice->id,
            'amount' => '50',
            'phone_number' => '0712345678',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_callback_with_mismatched_amount_does_not_complete_payment(): void
    {
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'sandbox-token',
                'expires_in' => '3599',
            ]),
            'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([
                'ResponseCode' => '0',
                'ResultCode' => '0',
            ]),
        ]);

        $invoice = $this->createInvoice('100.00');
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => '60.00',
            'payment_method' => 'mpesa',
            'phone_number' => '254712345678',
            'checkout_request_id' => 'ws_CO_test_003',
            'status' => 'pending',
        ]);

        $this->postJson(
            route('mpesa.callback'),
            $this->successCallback('ws_CO_test_003', 59)
        )->assertUnprocessable();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('unpaid', $invoice->fresh()->status);
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

    private function successCallback(string $checkoutRequestId, int $amount): array
    {
        return [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => $checkoutRequestId,
                    'ResultCode' => 0,
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => $amount],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCP12345678'],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
                            ['Name' => 'TransactionDate', 'Value' => (int) now('Africa/Nairobi')->format('YmdHis')],
                        ],
                    ],
                ],
            ],
        ];
    }
}
