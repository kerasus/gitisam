<?php

namespace App\Http\Controllers\Api;

use App\Traits\Filter;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\InvoiceDistribution;
use App\Http\Controllers\Controller;

class InvoiceDistributionController extends Controller
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
    public function index(Request $request): JsonResponse
    {
        $config = [
            'filterKeys' => [
                'distribution_method', 'amount'
            ],
            'filterRelationKeys' => [
                // Filtering by invoice relation
                [
                    'requestKey' => 'invoiceNumber',
                    'relationName' => 'invoice',
                    'relationColumn' => 'invoice_number'
                ],
                [
                    'requestKey' => 'invoiceAmount',
                    'relationName' => 'invoice',
                    'relationColumn' => 'amount'
                ],

                // Filtering by unit relation
                [
                    'requestKey' => 'unitUnitNumber',
                    'relationName' => 'unit',
                    'relationColumn' => 'unit_number'
                ],
                [
                    'requestKey' => 'unitType',
                    'relationName' => 'unit',
                    'relationColumn' => 'type'
                ],
                [
                    'requestKey' => 'unitArea',
                    'relationName' => 'unit',
                    'relationColumn' => 'area'
                ],
                [
                    'requestKey' => 'unitFloor',
                    'relationName' => 'unit',
                    'relationColumn' => 'floor'
                ],

                // Filtering by transactions relation
                [
                    'requestKey' => 'transactionAmount',
                    'relationName' => 'transactions',
                    'relationColumn' => 'amount'
                ],
                [
                    'requestKey' => 'transactionStatus',
                    'relationName' => 'transactions',
                    'relationColumn' => 'transaction_status'
                ]
            ],
            'eagerLoads' => [
                'invoice', 'unit'
            ],
            'setAppends' => [
                'status_label'
            ]
        ];

        return $this->commonIndex($request, InvoiceDistribution::class, $config);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'unit_id' => 'required|exists:units,id',
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:unpaid,paid,pending,cancelled',
        ]);

        return $this->commonStore($request, InvoiceDistribution::class);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $invoiceDistribution = InvoiceDistribution::with(['invoice', 'unit', 'transactions', ])->findOrFail($id);

        return $this->jsonResponseOk($invoiceDistribution);
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
        $invoiceDistribution = InvoiceDistribution::findOrFail($id);

        $request->validate([
            'invoice_id' => 'sometimes|required|exists:invoices,id',
            'unit_id' => 'sometimes|required|exists:units,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'status' => 'nullable|in:unpaid,paid,pending,cancelled',
        ]);

        return $this->commonUpdate($request, $invoiceDistribution);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $invoiceDistribution = InvoiceDistribution::findOrFail($id);

        return $this->commonDestroy($invoiceDistribution);
    }
}
