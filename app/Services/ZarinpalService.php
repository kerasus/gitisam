<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class ZarinpalService
{
    private $merchantId;
    private $callbackUrl;
    private $zarinpalBaseUrl;
    private $isSandbox;

    public function __construct()
    {
        // Initialize merchant ID, callback URL, and sandbox mode from configuration
        $this->merchantId = config('services.zarinpal.merchant_id');
        $this->callbackUrl = config('services.zarinpal.callback_url');
        $this->isSandbox = config('services.zarinpal.sandbox', false);

        // Set the base URL based on sandbox mode
        $this->zarinpalBaseUrl = $this->isSandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://payment.zarinpal.com/pg/v4/payment';
    }

    /**
     * Send a payment request to Zarinpal gateway.
     *
     * @param int $amount
     * @param string $description
     * @param string|null $mobile
     * @param string|null $email
     * @return array
     * @throws Exception
     */
    public function sendPaymentRequest(int $amount, string $description, ?string $mobile = null, ?string $email = null): array
    {
        $response = Http::asJson()->post("{$this->zarinpalBaseUrl}/request.json", [
            'merchant_id' => $this->merchantId,
            'amount' => $amount,
            'callback_url' => $this->callbackUrl . '?gt=zarinpal',
            'description' => $description,
            'metadata' => [
                'mobile' => $mobile,
                'email' => $email,
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json()['data'];
            if ($data['code'] === 100) {
                return [
                    'authority' => $data['authority'],
                    'redirect_url' => "https://".($this->isSandbox ? 'sandbox.' : '')."zarinpal.com/pg/StartPay/{$data['authority']}",
                ];
            } else {
                throw new Exception("Zarinpal error: {$data['message']}");
            }
        } else {
            throw new Exception('Failed to connect to Zarinpal gateway.');
        }
    }

    /**
     * Verify the payment transaction with Zarinpal.
     *
     * @param int $amount
     * @param string $authority
     * @return array
     * @throws Exception
     */
    public function verifyPayment(int $amount, string $authority): array
    {
        $response = Http::asJson()->post("{$this->zarinpalBaseUrl}/verify.json", [
            'merchant_id' => $this->merchantId,
            'amount' => $amount,
            'authority' => $authority,
        ]);

        if ($response->successful()) {
            $data = $response->json()['data'];
            if ($data['code'] === 100 || $data['code'] === 101) {
                return [
                    'status' => 'paid',
                    'ref_id' => $data['ref_id'],
                    'card_pan' => $data['card_pan'] ?? null,
                    'card_hash' => $data['card_hash'] ?? null,
                    'fee' => $data['fee'] ?? null,
                ];
            } else {
                throw new Exception("Zarinpal verification failed: {$data['message']}");
            }
        } else {
            throw new Exception('Failed to verify payment with Zarinpal.');
        }
    }
}
