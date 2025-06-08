<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\SamanTransaction;
use Illuminate\Support\Facades\Http;

class SamanGatewayService
{
    private $terminalId;
    private $callbackUrl;

    public function __construct()
    {
        // Initialize configuration values
        $this->terminalId = config('services.samanGateway.terminal_id');
        $this->callbackUrl = config('services.samanGateway.callback_url');
    }

    /**
     * Request a token from Saman Gateway.
     *
     * @param int $amount The amount of the transaction in Rials.
     * @param string $resNum The unique reference number for the transaction.
     * @param string|null $cellNumber Optional mobile number of the user.
     * @return string The token received from the gateway.
     * @throws Exception
     */
    private function requestToken(int $amount, string $resNum, ?string $cellNumber = null): string
    {
        $serverIp = gethostbyname(gethostname());

        $response = Http::asJson()->post('https://sep.shaparak.ir/onlinepg/onlinepg', [
            'action' => 'token',
            'TerminalId' => $this->terminalId,
            'Amount' => $amount,
            'ResNum' => $resNum,
            'RedirectUrl' => $this->callbackUrl . '?gt=saman',
            'CellNumber' => $cellNumber,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['status'] === 1) {
                return $data['token'];
            } else {
                throw new Exception("Failed to get token: {$data['errorDesc']} (Server IP: $serverIp)");
            }
        } else {
            throw new Exception('Failed to connect to Saman gateway.');
        }
    }

    /**
     * Redirect the user to the payment gateway by creating an HTML form.
     *
     * @param int $amount The amount of the transaction in Rials.
     * @param string $resNum The unique reference number for the transaction.
     * @param string|null $cellNumber Optional mobile number of the user.
     * @return Response The HTML form response.
     * @throws Exception
     */
    public function redirectToGateway(int $amount, string $resNum, ?string $cellNumber = null): Response
    {
        // Request token from Saman gateway
        $token = $this->requestToken($amount, $resNum, $cellNumber);

        // Create an HTML form to redirect the user to the gateway
        $form = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to Payment Gateway...</title>
</head>
<body>
    <form id="paymentForm" action="https://sep.shaparak.ir/OnlinePG/OnlinePG" method="post">
        <input type="hidden" name="Token" value="$token" />
        <input type="hidden" name="GetMethod" value="" />
    </form>
    <script>
        document.getElementById('paymentForm').submit();
    </script>
</body>
</html>
HTML;

        return response($form);
    }

    /**
     * Verify the payment transaction with Saman gateway.
     *
     * @param string $refNum The reference number of the transaction.
     * @return array The verification result.
     * @throws Exception
     */
    public function verifyPayment(string $refNum): array
    {
        $response = Http::asJson()->post('https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction', [
            'RefNum' => $refNum,
            'TerminalNumber' => $this->terminalId,
        ]);

        if ($response->successful()) {
            try {
                $data = $response->json();
            } catch (\Exception $e) {
                \Log::error("Error processing Saman gateway response: " . $e->getMessage());
                \Log::info("Raw response from Saman gateway: " . $response->body());
                throw $e;
            }
            $success = $data['Success'];

            if ($success === true || $success === 1) {
                $returnedData = [
                    'status' => 'paid',
                    'rrn' => $data['TransactionDetail']['RRN'] ?? null,
                    'ref_num' => $data['TransactionDetail']['RefNum'] ?? null,
                    'masked_pan' => $data['TransactionDetail']['MaskedPan'] ?? null,
                    'hashed_pan' => $data['TransactionDetail']['HashedPan'] ?? null,
                    'terminal_number' => $data['TransactionDetail']['TerminalNumber'] ?? null,
                    'original_amount' => $data['TransactionDetail']['OrginalAmount'] ?? null,
                    'affective_amount' => $data['TransactionDetail']['AffectiveAmount'] ?? null,
                    'trace_date' => $data['TransactionDetail']['StraceDate'] ?? null,
                    'trace_no' => $data['TransactionDetail']['StraceNo'] ?? null,
                    'wage' => null,
                    'result_code' => $data['ResultCode'] ?? null,
                    'result_description' => $data['ResultDescription'] ?? null,
                ];
                return $returnedData;
            } else {
                throw new Exception("Verification failed: {$data['ResultDescription']}");
            }
        } else {
            throw new Exception('Failed to verify payment with Saman gateway.');
        }
    }
}
