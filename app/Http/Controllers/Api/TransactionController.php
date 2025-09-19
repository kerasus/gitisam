<?php

namespace App\Http\Controllers\Api;

use App\Models\TransactionInvoiceDistribution;
use Exception;
use App\Models\Unit;
use App\Models\User;
use App\Traits\Filter;
use App\Traits\CommonCRUD;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\SamanTransaction;
use Illuminate\Http\JsonResponse;
use App\Services\ZarinpalService;
use Illuminate\Routing\Redirector;
use App\Http\Controllers\Controller;
use App\Services\SamanGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TransactionController extends Controller
{
    use Filter, CommonCRUD;
    protected $zarinpalService;
    protected $samanGatewayService;

    public function __construct(ZarinpalService $zarinpalService, SamanGatewayService $samanGatewayService)
    {
        // Apply authorization middleware if needed
        $this->middleware('auth:sanctum')->except(['redirectToGatewayDirect', 'handleCallback', 'getPublicData']);

        $this->zarinpalService = $zarinpalService;
        $this->samanGatewayService = $samanGatewayService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $config = [
            'filterKeys' => [
                'amount',
                'payment_method',
                'receipt_image',
                'authority',
                'transactionID',
                'transaction_status'
            ],
            'filterKeysExact'=> [
                'unit_id',
                'target_group',
                'transaction_type',
            ],
            'filterDate'=> [
                'paid_at'
            ],
            'filterRelationKeys' => [
                // Filtering by user relation
                [
                    'requestKey' => 'userName',
                    'relationName' => 'user',
                    'relationColumn' => 'name'
                ],
                [
                    'requestKey' => 'userEmail',
                    'relationName' => 'user',
                    'relationColumn' => 'email'
                ],
                [
                    'requestKey' => 'userMobile',
                    'relationName' => 'user',
                    'relationColumn' => 'mobile'
                ],

                [
                    'requestKey' => 'unitNumber',
                    'relationName' => 'unit',
                    'relationColumn' => 'unit_number',
                    'exact' => true
                ]
            ],
            'eagerLoads' => [
                'unit',
                'images',
                'invoiceDistributions'
            ],
            'setAppends' => [
                'target_group_label',
                'payment_method_label',
                'transaction_status_label'
            ]
        ];

        return $this->commonIndex($request, Transaction::class, $config);
    }

    /**
     * Store Payment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the transaction data
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'target_group' => 'required|in:resident,owner',
            'payment_method' => 'required|in:bank_gateway_saman,bank_gateway_zarinpal,mobile_banking,atm,cash,check',
            'authority' => 'nullable|string|unique:transactions,authority',
            'transactionID' => 'nullable|string|unique:transactions,transactionID',
            'transaction_status' => 'required|in:transferred_to_pay,unsuccessful,paid,unpaid',
            'paid_at' => 'nullable|date_format:Y-m-d H:i:s',
            'unit_id' => 'required|exists:units,id'
        ]);

        // Extract transaction data
        $transactionData = [
            'unit_id' => $request->input('unit_id'),
            'amount' => $request->input('amount'),
            'payment_method' => $request->input('payment_method'),
            'target_group' => $request->input('target_group'),
            'authority' => $request->input('authority'),
            'transactionID' => $request->input('transactionID'),
            'transaction_status' => $request->input('transaction_status'),
            'transaction_type' => 'unit_transaction',
            'paid_at' => $request->input('paid_at'),
        ];

        // Create the transaction
        $transaction = Transaction::create($transactionData);

        // Return the response with the created transaction and its distributions
        $transaction->load('invoiceDistributions.invoice'); // Load related invoice distributions and invoices

        return $this->jsonResponseOk($transaction, 'Transaction and distributions created successfully.');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeIncome(Request $request): JsonResponse
    {
        // Validate the transaction data
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_gateway_saman,bank_gateway_zarinpal,mobile_banking,atm,cash,check',
            'authority' => 'nullable|string|unique:transactions,authority',
            'transactionID' => 'nullable|string|unique:transactions,transactionID',
            'transaction_status' => 'required|in:transferred_to_pay,unsuccessful,paid,unpaid',
            'building_id' => 'required|exists:units,id'
        ]);

        // Extract transaction data
        $transactionData = [
            'building_id' => $request->input('building_id'),
            'amount' => $request->input('amount'),
            'payment_method' => $request->input('payment_method'),
            'authority' => $request->input('authority'),
            'transactionID' => $request->input('transactionID'),
            'transaction_status' => $request->input('transaction_status'),
            'transaction_type' => 'building_income',
        ];

        // Create the transaction
        $transaction = Transaction::create($transactionData);

        $transaction->building->updateBalance();


        return $this->jsonResponseOk($transaction, 'Transaction and distributions created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::with(['unit.users', 'unit.residents', 'unit.owners', 'invoiceDistributions.invoice', 'images', 'samanTransaction'])->findOrFail($id);

        return $this->jsonResponseOk($transaction);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getPublicData(int $id): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);

        return $this->jsonResponseOk($transaction);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);

        $request->validate([
            'user_id' => 'sometimes|nullable|exists:users,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'payment_method' => 'sometimes|required|in:bank_gateway_saman,bank_gateway_zarinpal,mobile_banking,atm,cash,check',
            'receipt_image' => 'sometimes|nullable|string',
            'authority' => 'sometimes|nullable|string|unique:transactions,authority,' . $id,
            'transactionID' => 'sometimes|nullable|string|unique:transactions,transactionID,' . $id,
            'transaction_status' => 'sometimes|required|in:transferred_to_pay,unsuccessful,paid,unpaid',
        ]);

        return $this->commonUpdate($request, $transaction);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);

        TransactionInvoiceDistribution::where('transaction_id', $transaction->id)->delete();

        $result = $this->commonDestroy($transaction);

        $transaction->building?->updateBalance();

        return $result;
    }

    /**
     * Redirect user to payment gateway via a direct link.
     */
    public function redirectToGatewayDirect(Request $request, $unit_id, $target_group)
    {
        try {
            // Validate the target group
            if (!in_array($target_group, ['resident', 'owner'])) {
                throw new \InvalidArgumentException("Invalid target group provided.");
            }

            $description = 'پرداخت از طریق لینک مستقیم';

            // Get optional amount from query parameter
            $amount = $request->query('amount');
            $debtAmount = 0;

            // Validate amount if provided
            if ($amount !== null) {
                if (!is_numeric($amount) || $amount <= 0) {
                    return response()->json([
                        'پیام' => 'مبلغ وارد شده نامعتبر است. باید عددی مثبت باشد.'
                    ], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
                }
                $debtAmount = (int) $amount;
            }

            // Call the common logic method
//            $processPaymentResult = $this->processZarinpalPaymentRequest(
//                $unit_id,
//                $targetGroup,
//                $description
//            );
//
//            // Extract data from the JsonResponse
//            $responseData = $processPaymentResult->getData(true); // Convert JSON to array
//
//            // Check if the redirect URL exists
//            if (isset($responseData['redirect_url'])) {
//                return redirect($responseData['redirect_url']);
//            }
//            return $responseData;

            return $this->processSamanGatewayPaymentRequest(
                $unit_id,
                $target_group,
                $description,
                $debtAmount
            );

        } catch (\Exception $e) {
            // Log the error for debugging purposes
            \Log::error("Error in redirectToGatewayDirect: " . $e->getMessage());

            // Redirect the user to the unit's page in case of an error
            return redirect($this->getRouteOfPublicUnitPageInClient($unit_id));
        }
    }

    private function getRouteOfPublicUnitPageInClient ($unitId) {
        $baseUrl = env('CLIENT_URL', '/');
        return "$baseUrl/#/public/unit/$unitId";
    }

    private function getRouteOfPublicTransactionPageInClient ($transactionId, $status) {
        $baseUrl = env('CLIENT_URL', '/');
        return "$baseUrl/#/payment-result?transaction_id={$transactionId}&status=$status";
    }

    /**
     * Redirect user to Zarinpal payment gateway via a custom request.
     */
    public function redirectToGateway(Request $request): Application|Response|JsonResponse|Redirector|\Illuminate\Contracts\Foundation\Application|RedirectResponse
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'amount' => 'required|integer|min:1000',
            'description' => 'required|string|max:255',
            'target_group' => 'required|in:resident,owner',
        ]);

        try {
            // Extract amount and description from the request
            $unitId = $validated['unit_id'];
            $amount = $validated['amount'];
            $description = $validated['description'];
            $targetGroup = $validated['target_group'];

            // Call the common logic method
//            return $this->processZarinpalPaymentRequest(
//                $unitId,
//                $targetGroup,
//                $description,
//                $amount
//            );
            return $this->processSamanGatewayPaymentRequest(
                $unitId,
                $targetGroup,
                $description,
                $amount
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Common logic for processing payment requests.
     */
    private function processZarinpalPaymentRequest(
        $unit_id,
        $targetGroup,
        $description = '-',
        $amount = 0,
        $user_id = null
    ): Application|JsonResponse|Redirector|\Illuminate\Contracts\Foundation\Application|RedirectResponse
    {
        try {
            $email = 'info@gitisam.ir';
            // Create a new transaction record
            $result = $this->createTransactionForPaymentRequest(
                $unit_id,
                $user_id,
                $targetGroup,
                $description,
                $amount,
                'bank_gateway_zarinpal'
            );

            // $unit = $result['unit'];
            $user = $result['user'];
            $amount = $result['amount'];
            $transaction = $result['transaction'];

            if ($amount <= 0) {
                return redirect($this->getRouteOfPublicUnitPageInClient($unit_id));
//                throw new Exception('No debt available for the specified unit.');
            }

            // Send payment request to Zarinpal
            $result = $this->zarinpalService->sendPaymentRequest(
                $amount,
                $description,
                $user->mobile,
                $email
            );

            // Update transaction with authority code
            $transaction->update(['authority' => $result['authority']]);

            // Return the redirect URL
            return response()->json(['redirect_url' => $result['redirect_url']]);
        } catch (\Exception $e) {
            \Log::error("Error in processZarinpalPaymentRequest: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function processSamanGatewayPaymentRequest(
        $unit_id,
        $targetGroup,
        $description = '-',
        $amount = 0,
        $user_id = null
    )
    {
        try {
            // Create a new transaction record
            $result = $this->createTransactionForPaymentRequest(
                                $unit_id,
                                $user_id,
                                $targetGroup,
                                $description,
                                $amount,
                                'bank_gateway_saman'
                        );

            // $unit = $result['unit'];
            $user = $result['user'];
            $amount = $result['amount'];
            $transaction = $result['transaction'];

            if ($amount <= 0) {
                return redirect($this->getRouteOfPublicUnitPageInClient($unit_id));
            }
            if ($transaction) {
                // Send payment request to Saman Gateway
                $redirectHTMLFormAsResponse = $this->samanGatewayService->redirectToGateway(
                    $amount,
                    $transaction->id,
                    $user->mobile
                );

                // Return HTML form for redirect to gateway
                return $redirectHTMLFormAsResponse;
            }
            return redirect($this->getRouteOfPublicUnitPageInClient($unit_id));
        } catch (\Exception $e) {
            \Log::error("Error in processSamanGatewayPaymentRequest: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function createTransactionForPaymentRequest(
        $unit_id,
        $user_id,
        $targetGroup,
        $description = '-',
        $amount = 0,
        $paymentMethod = 'bank_gateway_saman'
    )
    {
        try {
            $email = 'info@gitisam.ir';

            // Find the unit by ID
            $unit = Unit::findOrFail($unit_id);

            // Get the first owner or resident of the unit
            $user = null;
            if ($user_id) {
                $user = User::findOrFail($user_id);
            } else if ($targetGroup === 'owner') {
                $user = $unit->owners()->first();
            } else if ($targetGroup === 'resident') {
                $user = $unit->residents()->first();
            }

            if (!$user) {
                throw new Exception('No user found for the specified unit.');
            }

            if ($amount === 0) {
                $amount = ($targetGroup === 'resident' ? $unit->current_resident_balance : $unit->current_owner_balance) * -1;
            }

            $transaction = null;

            // Validate the amount
            if ($amount > 0) {
                // Create a new transaction record
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'unit_id' => $unit->id,
                    'mobile' => $user->mobile,
                    'email' => $email,
                    'payment_method' => $paymentMethod,
                    'transaction_type' => 'unit_transaction',
                    'target_group' => $targetGroup,
                    'transaction_status' => 'transferred_to_pay',
                    'amount' => $amount,
                    'description' => $description,
                ]);
            }


            return [
                'user' => $user,
                'unit' => $unit,
                'amount' => $amount,
                'transaction' => $transaction,
            ];
        } catch (\Exception $e) {
            \Log::error("Error in createTransactionForPaymentRequest: " . $e->getMessage());
            throw $e; // Re-throw the exception to be handled by the caller
        }
    }

    /**
     * Handle callback from Zarinpal gateway.
     */
    public function handleCallback(Request $request): Application|Redirector|\Illuminate\Contracts\Foundation\Application|RedirectResponse
    {
        $gatewayType = $request->query('gt'); // Extract 'gt' parameter (e.g., 'zarinpal' or 'saman')

        $status = false;
        $transaction = null;
        $samanTransaction = null;
        $updateTransactionData = null;
        $updateSamanTransactionData = null;

        if ($gatewayType === 'zarinpal') {
            $result = $this->handleCallback_zarinpal($request);
            $transaction = $result['transaction'];
            $updateTransactionData = $result['update_transaction'];
            if ($result) {
                $status = true;
            }
        } else if ($gatewayType === 'saman') {
            $result = $this->handleCallback_saman($request);
            $transaction = $result['transaction'];
            $updateTransactionData = $result['update_transaction'];
            $samanTransaction = $result['transaction_saman'];
            $updateSamanTransactionData = $result['update_transaction_saman'];
            $samanTransaction->update($updateSamanTransactionData);

            if ($result['status'] === 'paid') {
                $status = true;
            }
        }

        $transaction->update($updateTransactionData);
        if ($status) {
            return redirect($this->getRouteOfPublicTransactionPageInClient($transaction->id, 'success'));
        } else {
            return redirect($this->getRouteOfPublicTransactionPageInClient($transaction->id, 'failed'));
        }
//        $authority = $request->input('Authority');
//        $status = $request->input('Status');
//
//        try {
//            // Find the transaction by authority
//            $transaction = Transaction::where('authority', $authority)->firstOrFail();
//
//            // Check if payment was successful
//            if ($status === 'OK') {
//                // Verify the payment with Zarinpal
//                $verification = $this->zarinpalService->verifyPayment($transaction->amount, $authority);
//
//                // Update transaction status and details
//                $transaction->update([
//                    'transaction_status' => 'paid',
//                    'transactionID' => $verification['ref_id'],
//                    'card_pan' => $verification['card_pan'],
//                    'card_hash' => $verification['card_hash'],
//                    'fee' => $verification['fee'],
//                    'verified_at' => now(),
//                ]);
//
//                // Redirect to a hash-based URL with success status
//                return redirect($this->getRouteOfPublicTransactionPageInClient($transaction->id, 'success'));
//            } else {
//                // Mark transaction as unsuccessful
//                $transaction->update(['transaction_status' => 'unsuccessful']);
//
//                // Redirect to a hash-based URL with failure status
//                return redirect($this->getRouteOfPublicTransactionPageInClient($transaction->id, 'failed'));
//            }
//        } catch (\Exception $e) {
//            // Log the error and redirect to a failure page
//            \Log::error('Zarinpal callback error: ' . $e->getMessage());
//
//            // Redirect to a hash-based URL with failure status
//            return redirect($this->getRouteOfPublicTransactionPageInClient($transaction->id, 'failed'));
//        }
    }

    private function handleCallback_zarinpal (Request $request) {

        $authority = $request->input('Authority');
        $status = $request->input('Status');

        try {
            // Find the transaction by authority
            $transaction = Transaction::where('authority', $authority)->firstOrFail();

            // Check if payment was successful
            if ($status === 'OK') {
                // Verify the payment with Zarinpal
                $verification = $this->zarinpalService->verifyPayment($transaction->amount, $authority);

                return [
                    'transaction' => $transaction,
                    'update_transaction' =>[
                        'transaction_status' => 'paid',
                        'transactionID' => $verification['ref_id'],
                        'card_pan' => $verification['card_pan'],
                        'card_hash' => $verification['card_hash'],
                        'fee' => $verification['fee'],
                        'paid_at' => now(),
                        'verified_at' => now(),
                    ],
                    'transaction_saman' => null,
                    'update_transaction_saman' => null,
                ];
            } else {
                return [
                    'status' => 'unsuccessful',
                    'transaction' => $transaction,
                    'update_transaction' =>[
                        'transaction_status' => 'unsuccessful'
                    ],
                    'transaction_saman' => null,
                    'update_transaction_saman' => null,
                ];
            }
        } catch (\Exception $e) {
            // Log the error and redirect to a failure page
//            \Log::error('Zarinpal callback error: ' . $e->getMessage());

            // Redirect to a hash-based URL with failure status
            return redirect($this->getRouteOfPublicTransactionPageInClient($transaction->id, 'failed'));
        }
    }

    private function handleCallback_saman (Request $request) {
        $refNum = $request->input('RefNum');
        $resNum = $request->input('ResNum');
        $traceNo = $request->input('TraceNo');
        $state = $request->input('State');
        $status = $request->input('Status');
        $rrn = $request->input('RRN');
        $securePan = $request->input('SecurePan');
        $hashedCardNumber = $request->input('HashedCardNumber');

        $transactionId = $resNum;
        $updateTransactionSaman = [];
        try {
            $transaction = Transaction::findOrFail($transactionId);
            $samanTransaction = SamanTransaction::create([
                'transaction_id' => $transaction->id,
                'ref_num' => $refNum,
                'res_num' => $resNum,
                'rrn' => $rrn,
                'secure_pan' => $securePan,
                'hashed_card_number' => $hashedCardNumber,
                'trace_no' => $traceNo
            ]);
            if ((int)$status === 2) {
                $verification = $this->samanGatewayService->verifyPayment($refNum);
                $updateTransactionSaman = [
                    'original_amount' => $verification['original_amount'] ?? null,
                    'affective_amount' => $verification['affective_amount'] ?? null,
                    's_trace_date' => $verification['trace_date'] ?? null,
                    's_trace_no' => $verification['trace_no'] ?? null,
                    'wage' => $verification['wage'] ?? null,
                    'result_code' => $verification['result_code'] ?? null,
                    'result_description' => $verification['result_description'] ?? null,
                ];

                if ($verification['status'] === 'paid') {
                    return [
                        'status' => 'paid',
                        'verification' => $verification,
                        'transaction' => $transaction,
                        'update_transaction' =>[
                            'transaction_status' => 'paid',
                            'transactionID' => $verification['ref_num'],
                            'card_pan' => $verification['masked_pan'] ?? null,
                            'card_hash' => $verification['hashed_pan'] ?? null,
                            'fee' => $verification['wage'] ?? null,
                            'paid_at' => now(),
                            'verified_at' => now(),
                        ],
                        'transaction_saman' => $samanTransaction,
                        'update_transaction_saman' => $updateTransactionSaman
                    ];
                }
                return [
                    'status' => 'unsuccessful',
                    'verification' => $verification,
                    'transaction' => $transaction,
                    'update_transaction' =>[
                        'transaction_status' => 'unsuccessful',
                        'transactionID' => $verification['ref_num'],
                        'card_pan' => $verification['masked_pan'] ?? null,
                        'card_hash' => $verification['hashed_pan'] ?? null,
                        'fee' => $verification['wage'] ?? null,
                    ],
                    'transaction_saman' => $samanTransaction,
                    'update_transaction_saman' => $updateTransactionSaman,
                ];
            } else {
                return [
                    'status' => 'unsuccessful',
                    'verification' => null,
                    'transaction' => $transaction,
                    'update_transaction' =>[
                        'transaction_status' => 'unsuccessful'
                    ],
                    'transaction_saman' => $samanTransaction,
                    'update_transaction_saman' => $updateTransactionSaman,
                ];
            }
        } catch (\Exception $e) {
            // Log the error and redirect to a failure page
//            \Log::error('saman callback error: ' . $e->getMessage());

            // Redirect to a hash-based URL with failure status
            return redirect($this->getRouteOfPublicTransactionPageInClient($transaction->id, 'failed'));
        }
    }

    /**
     * Attach a single image to an existing invoice.
     *
     * @param Request $request
     * @param int $transactionId
     * @return JsonResponse
     */
    public function attachImage(Request $request, int $transactionId): JsonResponse
    {
        // Validate the request data
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB per image
        ]);

        try {
            // Find the invoice by ID
            $transaction = Transaction::findOrFail($transactionId);

            // Handle uploaded image
            if ($request->hasFile('image')) {
                // Store the image in the 'public/invoices' directory
                $path = $request->file('image')->store('payment-transactions', 'public');

                // Create an image record associated with the invoice
                $transaction->images()->create([
                    'path' => $path,
                ]);
            }

            return response()->json([
                'message' => 'Image attached to the invoice successfully.',
                'data' => $transaction->load('images'), // Include the associated images in the response
            ], ResponseAlias::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred while attaching the image to the invoice.',
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Detach an image from an existing invoice and delete it from storage.
     *
     * @param $transactionId
     * @param int $imageId
     * @return JsonResponse
     */
    public function detachImage($transactionId, $imageId): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);

            $image = $transaction->images()->where('id', $imageId)->firstOrFail();

            Storage::disk('public')->delete($image->path);

            $image->delete();

            return response()->json([
                'message' => 'Image detached from the invoice successfully.',
                'data' => $transaction->load('images'),
            ], ResponseAlias::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error("Error detaching image from invoice: " . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred while detaching the image from the invoice.',
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
