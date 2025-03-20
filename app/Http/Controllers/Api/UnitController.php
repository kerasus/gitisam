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
    public function index(Request $request)
    {
        $config = [
            'filterKeys' => [
                'name', 'floor', 'number'
            ],
            'eagerLoads' => [
                'building', 'images', 'users'
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
    public function store(Request $request)
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
    public function show($id)
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
    public function update(Request $request, Unit $unit)
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
    public function destroy(Unit $unit)
    {
        return $this->commonDestroy($unit);
    }
}
