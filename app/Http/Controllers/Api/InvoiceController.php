<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Traits\Filter;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class InvoiceController extends Controller
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
                'title', 'status'
            ],
            'eagerLoads' => [
                'invoiceDistributions.unit', 'invoiceDistributions.user'
            ],
            'setAppends' => [
                'status_label'
            ]
        ];

        return $this->commonIndex($request, Invoice::class, $config);
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'invoice_type_id' => 'required|exists:invoice_types,id',
        ]);

        return $this->commonStore($request, Invoice::class);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::with(['invoiceDistributions.unit', 'invoiceDistributions.user'])->findOrFail($id);

        return $this->jsonResponseOk($invoice);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Invoice $invoice
     * @return JsonResponse
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'sometimes|required|numeric|min:0',
            'due_date' => 'sometimes|required|date',
            'invoice_type_id' => 'sometimes|required|exists:invoice_types,id',
        ]);

        return $this->commonUpdate($request, $invoice);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Invoice $invoice
     * @return JsonResponse
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        return $this->commonDestroy($invoice);
    }
}
