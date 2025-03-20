<?php

namespace App\Http\Controllers\Api;

use App\Traits\Filter;
use App\Models\UnitUser;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class UnitUserController extends Controller
{
    use Filter, CommonCRUD;

    public function __construct()
    {
        // Apply authorization middleware if needed
        $this->middleware('auth:sanctum');
    }

    /**
     * Display all users assigned to a specific unit.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $config = [
            'filterRelationKeys'=> [
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
                [
                    'requestKey' => 'unitNumberOfRooms',
                    'relationName' => 'unit',
                    'relationColumn' => 'number_of_rooms'
                ],
                [
                    'requestKey' => 'unitParkingSpaces',
                    'relationName' => 'unit',
                    'relationColumn' => 'parking_spaces'
                ],
                [
                    'requestKey' => 'unitResidentName',
                    'relationName' => 'unit',
                    'relationColumn' => 'resident_name'
                ],
                [
                    'requestKey' => 'unitResidentPhone',
                    'relationName' => 'unit',
                    'relationColumn' => 'resident_phone'
                ],
                [
                    'requestKey' => 'unitOwnerName',
                    'relationName' => 'unit',
                    'relationColumn' => 'owner_name'
                ],
                [
                    'requestKey' => 'unitOwnerPhone',
                    'relationName' => 'unit',
                    'relationColumn' => 'owner_phone'
                ],
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
                    'requestKey' => 'userUsername',
                    'relationName' => 'user',
                    'relationColumn' => 'username'
                ],
                [
                    'requestKey' => 'userMobile',
                    'relationName' => 'user',
                    'relationColumn' => 'mobile'
                ]
            ],
            'eagerLoads' => [
                'unit', 'user'
            ]
        ];

        return $this->commonIndex($request, UnitUser::class, $config);
    }

    /**
     * Assign multiple users to specific units (bulk store).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.unit_id' => 'required|exists:units,id',
        ]);

        $assignments = $request->input('assignments');

        // Create multiple UnitUser records
        $createdRecords = UnitUser::insert(
            array_map(function ($assignment) {
                return [
                    'user_id' => $assignment['user_id'],
                    'unit_id' => $assignment['unit_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $assignments)
        );

        return $this->jsonResponseOk([
            'message' => 'Bulk assignment created successfully',
            'data' => $assignments,
        ]);
    }

    /**
     * Assign a user to a specific unit.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'unit_id' => 'required|exists:units,id',
        ]);

        return $this->commonStore($request, UnitUser::class);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $unitUser = UnitUser::with(['unit', 'user'])->findOrFail($id);

        return $this->jsonResponseOk($unitUser);
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

        $unitUser = UnitUser::findOrFail($id);

        $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'unit_id' => 'sometimes|required|exists:units,id',
        ]);

        return $this->commonUpdate($request, $unitUser);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $unitUser = UnitUser::findOrFail($id);

        return $this->commonDestroy($unitUser);
    }
}
