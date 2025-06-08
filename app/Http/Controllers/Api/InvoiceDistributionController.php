<?php

namespace App\Http\Controllers\Api;

use App\Models\Unit;
use App\Traits\Filter;
use App\Models\Invoice;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\InvoiceDistribution;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\InvoiceDistributionBalanceUpdater;
use App\Services\InvoiceDistributionCalculatorService;

class InvoiceDistributionController extends Controller
{
    use Filter, CommonCRUD;
    protected InvoiceDistributionBalanceUpdater $balanceUpdater;

    public function __construct(InvoiceDistributionBalanceUpdater $balanceUpdater)
    {
        // Apply authorization middleware if needed
        $this->middleware('auth:sanctum');
        $this->balanceUpdater = $balanceUpdater;
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
            'filterKeysExact'=> [
                'unit_id',
            ],
            'filterRelationKeys' => [
                // Filtering by invoice relation
                [
                    'requestKey' => 'invoiceNumber',
                    'relationName' => 'invoice',
                    'relationColumn' => 'invoice_number'
                ],
                [
                    'requestKey' => 'invoiceCategory',
                    'relationName' => 'invoice',
                    'relationColumn' => 'invoice_category_id'
                ],
                [
                    'requestKey' => 'invoiceAmount',
                    'relationName' => 'invoice',
                    'relationColumn' => 'amount'
                ],
                [
                    'requestKey' => 'invoiceTargetGroup',
                    'relationName' => 'invoice',
                    'relationColumn' => 'target_group',
                    'exact' => true
                ],

                // Filtering by unit relation
                [
                    'requestKey' => 'unit_number',
                    'relationName' => 'unit',
                    'relationColumn' => 'unit_number',
                    'exact' => true
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
                'invoice.invoiceCategory', 'unit'
            ],
            'setAppends' => [
                'status_label',
                'current_balance',
                'distribution_method_label',
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
        $invoiceDistribution = InvoiceDistribution::with(['invoice.invoiceCategory', 'invoice.images', 'unit', 'transactions', ])->findOrFail($id);

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
        // Find the invoice distribution by ID
        $invoiceDistribution = InvoiceDistribution::findOrFail($id);

        // Validate the incoming request
        $request->validate([
            'description' => 'sometimes|nullable|string', // Allow updating the description field
            'status' => 'sometimes|nullable|in:unpaid,paid,pending,cancelled', // Allow updating the status field
        ]);

        // Update only the allowed fields (description and status)
        $updatedData = $request->only(['description', 'status']);

        // Update the record
        $invoiceDistribution->update($updatedData);

        $this->balanceUpdater->updateBalances($invoiceDistribution, false);

        // Return the response with the updated record
        return response()->json([
            'message' => 'توزیع فاکتور با موفقیت به‌روزرسانی شد.',
            'data' => $invoiceDistribution,
        ]);
    }

    /**
     * Bulk store multiple invoice distributions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkStore(Request $request): JsonResponse
    {
        // Validate the incoming request
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'distribution_method' => 'required|in:equal,per_person,area,parking,custom',
            'distributions' => 'required|array',
            'distributions.*.unit_id' => 'required|exists:units,id',
            'distributions.*.status' => 'nullable|in:unpaid,paid,pending,cancelled',
            'distributions.*.amount' => 'nullable|integer|min:0', // Ensure amounts are integers
        ]);

        try {
            // Extract validated data
            $invoiceId = $request->input('invoice_id');
            $distributionMethod = $request->input('distribution_method');
            $unitIds = collect($request->input('distributions'))->pluck('unit_id')->toArray();

            // Fetch the total amount of the invoice
            $invoice = Invoice::findOrFail($invoiceId);
            $totalInvoiceAmount = $invoice->amount;

            // Delete all existing distributions for the invoice
            $existingDistributions = InvoiceDistribution::where('invoice_id', $invoiceId)->get();
            foreach ($existingDistributions as $existingDistribution) {
                $existingDistribution->delete();
            }

            // Use the service to calculate amounts and descriptions
            $calculatorService = new InvoiceDistributionCalculatorService();
            $calculatedDistributions = $calculatorService->calculate($distributionMethod, $unitIds, $totalInvoiceAmount);

            // Create records in bulk
            $createdDistributions = $calculatedDistributions->map(function ($data) use ($invoiceId, $distributionMethod) {
                return InvoiceDistribution::create([
                    'invoice_id' => $invoiceId,
                    'unit_id' => $data['unit_id'],
                    'distribution_method' => $distributionMethod,
                    'amount' => $data['amount'],
                    'description' => $data['description'],
                    'status' => 'unpaid', // Default to 'unpaid' if status is not provided
                ]);
            });

            // Call updateBalances for all created records in bulk
            foreach ($createdDistributions as $distribution) {
                $this->balanceUpdater->updateBalances($distribution);
            }

            // Return the response with the created records
            return response()->json([
                'message' => 'توزیع‌های فاکتور با موفقیت ایجاد شدند.',
                'data' => $createdDistributions,
            ]);

        } catch (\Exception $e) {
            \Log::error("Error during bulk store of invoice distributions: " . $e->getMessage());
            return response()->json([
                'message' => 'خطا در ایجاد توزیع‌های فاکتور.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param InvoiceDistribution $invoiceDistribution
     * @return JsonResponse
     */
    public function destroy(InvoiceDistribution $invoiceDistribution): JsonResponse
    {
        try {
            // Handle the deletion of the invoice distribution
            $invoiceDistribution->handleDeletion();

            // Return a success response
            return response()->json([
                'message' => 'توزیع فاکتور با موفقیت حذف شد.',
            ]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error("Error deleting invoice distribution ID {$invoiceDistribution->id}: " . $e->getMessage());

            // Return an error response
            return response()->json([
                'message' => 'خطا در حذف توزیع فاکتور.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
