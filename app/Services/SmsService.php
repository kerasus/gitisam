<?php

namespace App\Services;

use Exception;
use App\Models\SmsMessage;
use Ipe\Sdk\Facades\SmsIr;
use Ipe\Sdk\Exceptions\SmsException;

class SmsService
{
    /**
     * Send a verification SMS using a template.
     */
    public function sendVerificationSms($mobile, $templateId, $parameters, $unitId = null)
    {
        try {
            // Send the SMS using the SMS.ir service
            $response = SmsIr::verifySend($mobile, $templateId, $parameters);

            // Extract status, message, and data from the response
            $status = $response->status ?? null;
            $message = $response->message ?? null;
            $data = $response->data ?? null;

            // Check if the response status indicates an error
            if ($status !== 1) {
                throw new \Exception($message ?? 'An unexpected error occurred while sending SMS.');
            }

            // Extract MessageId from the data field
            $messageId = $data['messageId'] ?? null;

            // Save the SMS record in the database
            $smsMessage = SmsMessage::create([
                'unit_id' => $unitId,
                'mobile' => $mobile,
                'message' => json_encode($parameters), // Store parameters as JSON
                'template_id' => $templateId,
                'status' => $messageId ? 'sent' : 'failed',
                'message_id' => $messageId,
                'sent_at' => now(),
            ]);

            return $smsMessage;
        } catch (SmsException $e) {
//            var_dump("SmsException", $e->getMessage());
            // Log the detailed error for debugging
            \Log::error("SMS.ir Error: " . $e->getMessage());

            // Re-throw the exception to propagate it to the caller
            throw new \Exception("SMS.ir Error: " . $e->getMessage());
        } catch (\Exception $e) {
//            var_dump("Exception");
            // Log any other unexpected errors
            \Log::error("General Error sending SMS: " . $e->getMessage());

            // Re-throw the exception to propagate it to the caller
            throw new \Exception("An unexpected error occurred while sending SMS: " . $e->getMessage());
        }
    }

    /**
     * Update the status of an SMS message based on its message ID.
     */
    public function updateSmsStatus(SmsMessage $smsMessage)
    {
        try {
            // Get the report for the message using its ID
            $report = SmsIr::getReportByMessageId($smsMessage->message_id);

            // Update the status of the SMS message
            $smsMessage->update([
                'status' => $report['Status'] ?? 'failed',
            ]);
        } catch (\Exception $e) {
            \Log::error("Error updating SMS status: " . $e->getMessage());
        }
    }

    /**
     * Get the account balance (credit) from SMS.ir.
     *
     * @return array The account balance information.
     * @throws Exception If an error occurs while fetching the balance.
     */
    public function getAccountBalance()
    {
        try {
            // Fetch the account credit using the SMS.ir service
            $response = SmsIr::getCredit();

            // Extract status, message, and data from the response
            $status = $response->status ?? null;
            $message = $response->message ?? null;
            $credit = $response->data ?? null;

            // Check if the response status indicates an error
            if ($status !== 1) {
                throw new \Exception($message ?? 'An unexpected error occurred while fetching account balance.');
            }

            return [
                'status' => 'success',
                'message' => 'Account balance fetched successfully.',
                'balance' => $credit,
            ];
        } catch (\Ipe\Sdk\Exceptions\SmsException $e) {
            // Log the detailed error for debugging
            \Log::error("SMS.ir Error: " . $e->getMessage());

            // Return the error message in the API response
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            // Log any other unexpected errors
            \Log::error("General Error fetching account balance: " . $e->getMessage());

            // Return the error message in the API response
            return [
                'status' => 'error',
                'message' => 'An unexpected error occurred while fetching account balance.',
            ];
        }
    }
}
