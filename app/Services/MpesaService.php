<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaService
{
    public function initiateStkPush(Invoice $invoice, int $amount, string $phoneNumber): array
    {
        $this->ensureConfigured();

        if ($invoice->currency_code !== 'KES') {
            throw new RuntimeException('M-Pesa payments are available for KES invoices only.');
        }

        if ($amount < 1) {
            throw new RuntimeException('The M-Pesa amount must be at least KES 1.');
        }

        $timestamp = now('Africa/Nairobi')->format('YmdHis');
        $shortcode = (string) config('services.mpesa.shortcode');
        $password = base64_encode(
            $shortcode.config('services.mpesa.passkey').$timestamp
        );
        $callbackUrl = (string) config('services.mpesa.callback_url');

        if (! filter_var($callbackUrl, FILTER_VALIDATE_URL)
            || parse_url($callbackUrl, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException(
                'Set MPESA_CALLBACK_URL to a publicly reachable HTTPS callback URL before initiating M-Pesa payments.'
            );
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->timeout(20)
            ->post($this->endpoint('/mpesa/stkpush/v1/processrequest'), [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => config(
                    'services.mpesa.transaction_type',
                    'CustomerPayBillOnline'
                ),
                'Amount' => $amount,
                'PartyA' => $phoneNumber,
                'PartyB' => $shortcode,
                'PhoneNumber' => $phoneNumber,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => substr(
                    config('services.mpesa.account_reference_prefix', 'INVOICEX')
                        .'-'.$invoice->invoice_number,
                    0,
                    12
                ),
                'TransactionDesc' => 'Invoice '.$invoice->invoice_number,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Safaricom could not start the STK push (HTTP '
                .$response->status().').'
            );
        }

        $data = $response->json();
        if (! is_array($data) || (string) ($data['ResponseCode'] ?? '') !== '0') {
            throw new RuntimeException(
                $data['ResponseDescription']
                    ?? 'Safaricom rejected the STK push request.'
            );
        }

        if (empty($data['CheckoutRequestID']) || empty($data['MerchantRequestID'])) {
            throw new RuntimeException('Safaricom returned an incomplete STK push response.');
        }

        return $data;
    }

    public function queryStkPush(string $checkoutRequestId): array
    {
        $this->ensureConfigured();

        $timestamp = now('Africa/Nairobi')->format('YmdHis');
        $shortcode = (string) config('services.mpesa.shortcode');
        $password = base64_encode(
            $shortcode.config('services.mpesa.passkey').$timestamp
        );

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->timeout(20)
            ->post($this->endpoint('/mpesa/stkpushquery/v1/query'), [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Safaricom could not verify the STK push (HTTP '
                .$response->status().').'
            );
        }

        $data = $response->json();
        if (! is_array($data) || (string) ($data['ResponseCode'] ?? '') !== '0') {
            throw new RuntimeException(
                $data['ResponseDescription']
                    ?? 'Safaricom did not accept the STK verification request.'
            );
        }

        return $data;
    }

    private function accessToken(): string
    {
        $token = Cache::get('mpesa.daraja_access_token');
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $response = Http::acceptJson()
            ->withBasicAuth(
                (string) config('services.mpesa.consumer_key'),
                (string) config('services.mpesa.consumer_secret')
            )
            ->timeout(10)
            ->get($this->endpoint('/oauth/v1/generate'), [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Safaricom authentication failed (HTTP '.$response->status().').'
            );
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Safaricom returned no access token.');
        }

        $expiresIn = max(60, (int) ($data['expires_in'] ?? 3600) - 60);
        Cache::put('mpesa.daraja_access_token', $accessToken, now()->addSeconds($expiresIn));

        return $accessToken;
    }

    private function ensureConfigured(): void
    {
        foreach ([
            'consumer_key',
            'consumer_secret',
            'shortcode',
            'passkey',
        ] as $key) {
            if (! is_string(config('services.mpesa.'.$key))
                || config('services.mpesa.'.$key) === '') {
                throw new RuntimeException(
                    'M-Pesa is not configured. Set the Daraja credentials in the local .env file.'
                );
            }
        }
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.mpesa.base_url'), '/').$path;
    }
}
