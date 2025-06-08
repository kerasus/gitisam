<?php

namespace App\Http\Controllers\Api;

use App\Traits\Filter;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use App\Models\InvoiceCategory;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class InvoiceCategoryController extends Controller
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
                'name', 'description'
            ]
        ];

        return $this->commonIndex($request, InvoiceCategory::class, $config);
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return $this->commonStore($request, InvoiceCategory::class);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $invoiceType = InvoiceCategory::findOrFail($id);

        return $this->jsonResponseOk($invoiceType);
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
        $invoiceType = InvoiceCategory::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return $this->commonUpdate($request, $invoiceType);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $invoiceType = InvoiceCategory::findOrFail($id);

        return $this->commonDestroy($invoiceType);
    }
}
