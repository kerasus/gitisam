<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Models\InvoiceCategory;
use App\Traits\Filter;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Storage;
use App\Models\Image;

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
    public function index(Request $request)
    {
        $config = [
            'filterKeys' => [
                'title',
                'description',
                'status'
            ],
            'filterKeysExact'=> [
                'target_group',
                'invoice_category_id',
                'is_covered_by_monthly_charge',
            ],
            'eagerLoads' => [
                'invoiceCategory'
//                'invoiceDistributions.unit.unitUser'
            ],
            'setAppends' => [
                'type_label',
                'status_label',
                'target_group_label'
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
            'invoice_category_id' => 'required|exists:invoice_categories,id',
            'type' => 'required|in:monthly_charge,planned_expense,unexpected_expense',
            'target_group' => 'required|in:resident,owner',
            'is_covered_by_monthly_charge' => 'required|boolean',
            'status' => 'nullable|in:unpaid,paid,pending,cancelled',
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
        $invoice = Invoice::with(['invoiceDistributions.unit.residents', 'invoiceDistributions.unit.owners', 'images'])->findOrFail($id);

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

    /**
     * Attach a single image to an existing invoice.
     *
     * @param Request $request
     * @param int $invoiceId
     * @return JsonResponse
     */
    public function attachImageToInvoice(Request $request, int $invoiceId): JsonResponse
    {
        // Validate the request data
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB per image
        ]);

        try {
            // Find the invoice by ID
            $invoice = Invoice::findOrFail($invoiceId);

            // Handle uploaded image
            if ($request->hasFile('image')) {
                // Store the image in the 'public/invoices' directory
                $path = $request->file('image')->store('invoices', 'public');

                // Create an image record associated with the invoice
                $invoice->images()->create([
                    'path' => $path,
                ]);
            }

            return response()->json([
                'message' => 'Image attached to the invoice successfully.',
                'data' => $invoice->load('images'), // Include the associated images in the response
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error("Error attaching image to invoice: " . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred while attaching the image to the invoice.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Detach an image from an existing invoice and delete it from storage.
     *
     * @param int $invoiceId
     * @param int $imageId
     * @return JsonResponse
     */
    public function detachImageFromInvoice($invoiceId, $imageId): JsonResponse
    {
        try {
            // Find the invoice by ID
            $invoice = Invoice::findOrFail($invoiceId);

            // Find the image by ID and ensure it belongs to the invoice
            $image = $invoice->images()->where('id', $imageId)->firstOrFail();

            // Delete the image file from storage
            Storage::disk('public')->delete($image->path);

            // Delete the image record from the database
            $image->delete();

            return response()->json([
                'message' => 'Image detached from the invoice successfully.',
                'data' => $invoice->load('images'), // Include the updated list of images in the response
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error("Error detaching image from invoice: " . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred while detaching the image from the invoice.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTotalExpensesByCategory(): JsonResponse
    {
        try {
            // Fetch all invoice categories with their related invoices
            $categories = InvoiceCategory::with('invoices')->get();

            // Calculate total expenses for each category
            $result = $categories->map(function ($category) {
                $totalPaidAmount = $category->invoices->sum('paid_amount');
                return [
                    'category_name' => $category->name,
                    'total_paid_amount' => $totalPaidAmount,
                    'total_amount' => $category->invoices->sum('amount'), // Optional: total amount of invoices
                ];
            });

            return response()->json($result, 200);
        } catch (\Exception $e) {
            \Log::error("Error fetching total expenses by category: " . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred while fetching total expenses.',
            ], 500);
        }
    }
}
