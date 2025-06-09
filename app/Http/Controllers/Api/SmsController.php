<?php

namespace App\Http\Controllers\Api;

use App\Models\Unit;
use App\Services\SmsService;
use App\Services\JalaliService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send monthly debt reminders to users.
     */
    public function sendMonthlyDebtReminders($target_group): JsonResponse
    {
        try {
            if (!in_array($target_group, ['resident', 'owner'])) {
                return response()->json([
                    'گروه پرداخت کننده نادرست است.'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($target_group === 'resident') {
                $units = Unit::whereRaw('(CAST(resident_base_balance AS SIGNED) + CAST(resident_paid_amount AS SIGNED) - CAST(resident_debt AS SIGNED)) < 0')
                    ->get();
                if ($units->isEmpty()) {
                    return response()->json([
                        'ساکن بدهکاری وجود ندارد.'
                    ], Response::HTTP_BAD_REQUEST);
                }
            } elseif ($target_group === 'owner') {
                $units = Unit::whereRaw('(CAST(owner_base_balance AS SIGNED) + CAST(owner_paid_amount AS SIGNED) - CAST(owner_debt AS SIGNED)) < 0')
                    ->get();
                if ($units->isEmpty()) {
                    return response()->json([
                        'مالک بدهکاری وجود ندارد.'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Initialize an array to store results
            $results = [];

            $jalaliService = new JalaliService();

            $shamsiDate = $jalaliService->toJalali();


            foreach ($units as $unit) {
                // Check if the unit has any outstanding debt
                $currentBalance = $target_group === 'resident' ? $unit->current_resident_balance : $unit->current_owner_balance;

                if ($currentBalance >= 0) {
                    continue; // Skip if the unit has any outstanding debt
                }

                $user = null;

                if ($target_group === 'resident') {
                    $user = $unit->residents()->first();
                } else
                if ($target_group === 'owner') {
                    $user = $unit->owners()->first();
                }

                if (!$user) {
                    $results[] = [
                        'unit_id' => $unit->id,
                        'status' => 'skipped',
                        'message' => 'No resident found for this unit.',
                    ];
                    continue; // Skip if no user is found
                }

                // Prepare the message parameters
                $parameters = [
                    [
                        'name' => 'RESIDENTNAME',
                        'value' => $user->name . ' ' . $user->lastname,
                    ],
                    [
                        'name' => 'SHAMSIDATE',
                        'value' => $shamsiDate,
                    ],
                    [
                        'name' => 'DEBTAMOUNT',
                        'value' => number_format($currentBalance * -1),
                    ],
                    [
                        'name' => 'UNITID',
                        'value' => $unit->id,
                    ],
                    [
                        'name' => 'TARGETGROUP',
                        'value' => $target_group
                    ],
                ];

                try {
                    // Send the SMS using the service
                    $this->smsService->sendVerificationSms(
                        $user->mobile,
                        '139675', // یادآوری پرداخت بدهی واحد
                        $parameters,
                        $unit->id
                    );

                    $results[] = [
                        'unit_id' => $unit->id,
                        'status' => 'success',
                        'message' => 'SMS sent successfully.',
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'unit_id' => $unit->id,
                        'status' => 'failed',
                        'message' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'message' => 'Monthly debt reminders processed.',
                'results' => $results,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the account balance (credit) from SMS.ir.
     *
     * @return JsonResponse The account balance information.
     */
    public function getAccountBalance(): JsonResponse
    {
        try {
            // Fetch the account balance using the SmsService
            $balanceInfo = $this->smsService->getAccountBalance();

            // Check if the operation was successful
            if ($balanceInfo['status'] === 'success') {
                return response()->json([
                    'message' => $balanceInfo['message'],
                    'balance' => $balanceInfo['balance'],
                ], Response::HTTP_OK);
            }

            // Return an error response if the operation failed
            return response()->json([
                'error' => $balanceInfo['message'],
            ], 400);
        } catch (\Exception $e) {
            // Log any unexpected errors
            \Log::error("Error fetching account balance: " . $e->getMessage());

            // Return a generic error response
            return response()->json([
                'error' => 'An unexpected error occurred while fetching account balance.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
