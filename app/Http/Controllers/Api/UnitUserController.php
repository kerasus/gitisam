<?php

namespace App\Http\Controllers\Api;

use App\Models\Unit;
use App\Traits\Filter;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
     * @param int $unitId
     * @return Response
     */
    public function index(Request $request, $unitId)
    {
        $config = [
            'filterKeys' => [
                'name', 'username', 'email', 'mobile'
            ],
            'filterRelationKeys'=> [
                [
                    'requestKey' => 'f_name',
                    'relationName' => 'account.user',
                    'relationColumn' => 'f_name'
                ],
                [
                    'requestKey' => 'l_name',
                    'relationName' => 'account.user',
                    'relationColumn' => 'l_name'
                ],
                [
                    'requestKey' => 'SSN',
                    'relationName' => 'account.user',
                    'relationColumn' => 'SSN'
                ]
            ],
            'eagerLoads' => [
                'units' // Example relationship (if applicable)
            ],
            'setAppends' => [
                // Add attributes to append here (if applicable)
            ]
        ];

        $unit = Unit::findOrFail($unitId);
        $modelQuery = $unit->users()->getQuery(); // Get the query builder for the relationship

        return $this->commonIndex($request, $modelQuery, $config);
    }

    /**
     * Assign a user to a specific unit.
     *
     * @param Request $request
     * @param int $unitId
     * @return Response
     */
    public function store(Request $request, $unitId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $unit = Unit::findOrFail($unitId);
        $user = User::findOrFail($request->user_id);

        // Attach the user to the unit
        $unit->users()->syncWithoutDetaching([$user->id]);

        return $this->jsonResponseOk([
            'message' => 'User assigned to unit successfully',
            'user' => $user,
        ]);
    }

    /**
     * Remove a user from a specific unit.
     *
     * @param int $unitId
     * @param int $userId
     * @return Response
     */
    public function destroy($unitId, $userId)
    {
        $unit = Unit::findOrFail($unitId);
        $user = User::findOrFail($userId);

        // Detach the user from the unit
        $unit->users()->detach($user->id);

        return $this->jsonResponseOk([
            'message' => 'User removed from unit successfully',
        ]);
    }
}
