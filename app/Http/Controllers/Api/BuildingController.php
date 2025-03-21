<?php

namespace App\Http\Controllers\Api;

use App\Traits\Filter;
use App\Models\Building;
use App\Traits\CommonCRUD;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
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
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
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
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $building = Building::findOrFail($id);

        return $this->jsonResponseOk($building);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Building $building
     * @return JsonResponse
     */
    public function update(Request $request, Building $building): JsonResponse
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
     * @return JsonResponse
     */
    public function destroy(Building $building): JsonResponse
    {
        return $this->commonDestroy($building);
    }
}
