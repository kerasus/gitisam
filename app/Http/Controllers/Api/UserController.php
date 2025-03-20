<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Traits\Filter;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class UserController extends Controller
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
                'name', 'username', 'email', 'mobile'
            ],
            'filterRelationKeys' => [
                // Filtering by related units (via unitUsers relationship)
                [
                    'requestKey' => 'unitUnitNumber',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'unit_number'
                ],
                [
                    'requestKey' => 'unitType',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'type'
                ],
                [
                    'requestKey' => 'unitArea',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'area'
                ],
                [
                    'requestKey' => 'unitFloor',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'floor'
                ],
                [
                    'requestKey' => 'unitNumberOfRooms',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'number_of_rooms'
                ],
                [
                    'requestKey' => 'unitParkingSpaces',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'parking_spaces'
                ],
                [
                    'requestKey' => 'unitResidentName',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'resident_name'
                ],
                [
                    'requestKey' => 'unitResidentPhone',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'resident_phone'
                ],
                [
                    'requestKey' => 'unitOwnerName',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'owner_name'
                ],
                [
                    'requestKey' => 'unitOwnerPhone',
                    'relationName' => 'unitUsers',
                    'relationColumn' => 'owner_phone'
                ],

                // Filtering by related transactions
                [
                    'requestKey' => 'transactionAmount',
                    'relationName' => 'transactions',
                    'relationColumn' => 'amount'
                ],
                [
                    'requestKey' => 'transactionPaymentMethod',
                    'relationName' => 'transactions',
                    'relationColumn' => 'payment_method'
                ],
                [
                    'requestKey' => 'transactionStatus',
                    'relationName' => 'transactions',
                    'relationColumn' => 'transaction_status'
                ]
            ],
            'eagerLoads' => [
                'unitUsers',
                'transactions'
            ]
        ];

        return $this->commonIndex($request, User::class, $config);
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
            'username' => 'required|string|unique:users',
            'mobile' => 'required|string|unique:users',
            'email' => 'nullable|string|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        return $this->commonStore($request, User::class);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return $this->jsonResponseOk($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|unique:users,username,' . $user->id,
            'mobile' => 'sometimes|required|string|unique:users,mobile,' . $user->id,
            'email' => 'nullable|string|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        return $this->commonUpdate($request, $user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        return $this->commonDestroy($user);
    }
}
