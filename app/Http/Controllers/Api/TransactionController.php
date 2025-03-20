<?php

namespace App\Http\Controllers\Api;

use App\Traits\Filter;
use App\Traits\CommonCRUD;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class TransactionController extends Controller
{
    use Filter, CommonCRUD;

    public function __construct()
    {
        // Apply authorization middleware if needed
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $config = [
            'filterKeys' => [
                'amount', 'payment_method', 'transaction_status'
            ],
            'eagerLoads' => [
                'user', 'invoiceDistributions' // Load relationships if applicable
            ],
            'setAppends' => [
                'payment_method_label', 'transaction_status_label' // Include labels in the response
            ]
        ];

        return $this->commonIndex($request, Transaction::class, $config);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:online,atm,pos,paycheck,wallet',
            'receipt_image' => 'nullable|string',
            'authority' => 'nullable|string|unique:transactions,authority',
            'transactionID' => 'nullable|string|unique:transactions,transactionID',
            'transaction_status' => 'required|in:transferred_to_pay,unsuccessful,successful,pending,archived_successful,unpaid,suspended,organizational_unpaid',
        ]);

        return $this->commonStore($request, Transaction::class);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $transaction = Transaction::with(['user', 'invoiceDistributions'])->findOrFail($id);

        return $this->jsonResponseOk($transaction);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $request->validate([
            'user_id' => 'sometimes|nullable|exists:users,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_method' => 'sometimes|required|in:online,atm,pos,paycheck,wallet',
            'receipt_image' => 'sometimes|nullable|string',
            'authority' => 'sometimes|nullable|string|unique:transactions,authority,' . $id,
            'transactionID' => 'sometimes|nullable|string|unique:transactions,transactionID,' . $id,
            'transaction_status' => 'sometimes|required|in:transferred_to_pay,unsuccessful,successful,pending,archived_successful,unpaid,suspended,organizational_unpaid',
        ]);

        return $this->commonUpdate($request, $transaction);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);

        return $this->commonDestroy($transaction);
    }
}
