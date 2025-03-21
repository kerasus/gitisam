<?php

namespace App\Http\Controllers\Api;

use App\Models\Unit;
use App\Traits\Filter;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class UnitController extends Controller
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
                'unit_number', 'type', 'area', 'floor', 'number_of_rooms',
                'parking_spaces', 'resident_name', 'resident_phone', 'owner_name', 'owner_phone'
            ],
            'filterRelationKeys' => [
                // Filtering by building relation
                [
                    'requestKey' => 'buildingName',
                    'relationName' => 'building',
                    'relationColumn' => 'name'
                ],
                [
                    'requestKey' => 'buildingAddress',
                    'relationName' => 'building',
                    'relationColumn' => 'address'
                ],

                // Filtering by users relation
                [
                    'requestKey' => 'userName',
                    'relationName' => 'users',
                    'relationColumn' => 'name'
                ],
                [
                    'requestKey' => 'userEmail',
                    'relationName' => 'users',
                    'relationColumn' => 'email'
                ],
                [
                    'requestKey' => 'userMobile',
                    'relationName' => 'users',
                    'relationColumn' => 'mobile'
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
                ],
                [
                    'requestKey' => 'transactionPaymentMethod',
                    'relationName' => 'transactions',
                    'relationColumn' => 'payment_method'
                ],

                // Filtering by invoiceDistributions relation
                [
                    'requestKey' => 'invoiceDistributionAmount',
                    'relationName' => 'invoiceDistributions',
                    'relationColumn' => 'amount'
                ],
                [
                    'requestKey' => 'invoiceDistributionStatus',
                    'relationName' => 'invoiceDistributions',
                    'relationColumn' => 'status'
                ]
            ],
            'eagerLoads' => [
                'building', 'images', 'unitUser.user'
            ]
        ];

        return $this->commonIndex($request, Unit::class, $config);
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
            'building_id' => 'required|exists:buildings,id',
            'floor' => 'nullable|integer',
            'number' => 'nullable|string',
            'area' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        return $this->commonStore($request, Unit::class);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $unit = Unit::findOrFail($id);

        return $this->jsonResponseOk($unit);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Unit $unit
     * @return JsonResponse
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'building_id' => 'sometimes|required|exists:buildings,id',
            'floor' => 'nullable|integer',
            'number' => 'nullable|string',
            'area' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        return $this->commonUpdate($request, $unit);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Unit $unit
     * @return JsonResponse
     */
    public function destroy(Unit $unit): JsonResponse
    {
        return $this->commonDestroy($unit);
    }
}
