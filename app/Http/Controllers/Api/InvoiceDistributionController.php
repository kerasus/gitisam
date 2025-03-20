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
    public function index(Request $request)
    {
        $config = [
            'filterKeys' => [
                'amount', 'status'
            ],
            'eagerLoads' => [
                'invoice', 'unit', 'user' // Load relationships if applicable
            ],
            'setAppends' => [
                'status_label' // Include the status label accessor in the response
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
    public function store(Request $request)
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
    public function show($id)
    {
        $invoiceDistribution = InvoiceDistribution::with(['invoice', 'unit', 'user'])->findOrFail($id);

        return $this->jsonResponseOk($invoiceDistribution);
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
    public function destroy($id)
    {
        $invoiceDistribution = InvoiceDistribution::findOrFail($id);

        return $this->commonDestroy($invoiceDistribution);
    }
}
