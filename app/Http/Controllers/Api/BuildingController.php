<?php

namespace App\Http\Controllers\Api;

use App\Traits\Filter;
use App\Models\Building;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

class BuildingController extends Controller
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
     * @return Response
     */
    public function index(Request $request)
    {
        $config = [
            'filterKeys' => [
                'name', 'address', 'city', 'district'
            ],
            'eagerLoads' => [
                'units', 'images'
            ]
        ];

        return $this->commonIndex($request, Building::class, $config);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        return $this->commonStore($request, Building::class);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $building = Building::findOrFail($id);

        return $this->jsonResponseOk($building);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Building $building
     * @return Response
     */
    public function update(Request $request, Building $building)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        return $this->commonUpdate($request, $building);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Building $building
     * @return Response
     */
    public function destroy(Building $building)
    {
        return $this->commonDestroy($building);
    }
}
