<?php

namespace App\Http\Controllers\Api;

use DB;
use Throwable;
use App\Models\Unit;
use App\Models\User;
use App\Traits\Filter;
use App\Traits\CommonCRUD;
use Illuminate\Support\Str;
use App\Services\SmsService;
use Illuminate\Http\Request;
use App\Services\JalaliService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\InvoiceDistributionBalanceUpdater;

class UnitController extends Controller
{
    use Filter, CommonCRUD;
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        // Apply authorization middleware if needed
        $this->middleware('auth:sanctum')->except(['getBalance', 'publicIndex']);
        $this->smsService = $smsService;
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
            'returnModelQuery' => true,
            'filterKeys' => [
                'type'
            ],
            'filterKeysExact'=> [
                'area',
                'floor',
                'unit_number',
                'building_id',
                'parking_spaces',
                'number_of_rooms',
                'number_of_residents',
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
            ],
            'filterRelationIds'=> [
                [
                    'requestKey' => 'user_id',
                    'relationName' => 'users'
                ]
            ],
            'eagerLoads' => [
                'building', 'images', 'owners', 'residents'
            ],
            'setAppends' => [
                'current_balance',
                'current_owner_balance',
                'current_resident_balance'
            ],
            'scopes'=> [
                'negativeBalance'
            ],
        ];

        $data = $this->commonIndex($request, Unit::class, $config);

        $modelQuery = $data['modelQuery'];
        $responseWithAttachedCollection = $data['responseWithAttachedCollection'];

        $modelQuery->orderByRaw('CAST(unit_number AS UNSIGNED) ASC');

        return $responseWithAttachedCollection($modelQuery);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $config = [
            'returnModelQuery' => true,
//            'select'=> [
//                'floor',
//                'unit_number',
//                'unit_number'
//            ],
            'filterKeysExact'=> [
                'unit_number',
                'building_id',
            ],
            'eagerLoads' => [
                'owners', 'residents'
            ],
            'setAppends' => [
                'current_balance',
                'current_owner_balance',
                'current_resident_balance'
            ],
            'scopes'=> [
                'negativeBalance'
            ],
        ];

        $data = $this->commonIndex($request, Unit::class, $config);

        $modelQuery = $data['modelQuery'];
        $responseWithAttachedCollection = $data['responseWithAttachedCollection'];

        $modelQuery->orderByRaw(
            '(
                     (COALESCE(CAST(resident_base_balance AS SIGNED), 0) + COALESCE(CAST(owner_base_balance AS SIGNED), 0)) +
                     (COALESCE(CAST(resident_paid_amount AS SIGNED), 0) + COALESCE(CAST(owner_paid_amount AS SIGNED), 0)) -
                     (COALESCE(CAST(resident_debt AS SIGNED), 0) + COALESCE(CAST(owner_debt AS SIGNED), 0))
             ) ASC'
        );
//        dd(Str::replaceArray('?', $modelQuery->getBindings(), $modelQuery->toSql()));

        return $responseWithAttachedCollection($modelQuery);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(Request $request): JsonResponse
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            // Validate the incoming request data
            $request->validate([
                'building_id' => 'required|exists:buildings,id', // Ensure the building exists
                'unit_number' => 'required|string|max:255', // Unit number is required
                'type' => 'required|in:residential,commercial', // Type must be either residential or commercial
                'area' => 'required|numeric|min:0', // Area is required and must be numeric
                'floor' => 'required|integer|min:0', // Floor is required and must be a non-negative integer
                'number_of_rooms' => 'required|integer|min:0', // Number of rooms is required and must be a non-negative integer
                'number_of_residents' => 'required|integer|min:0', // Number of residents is optional but must be a non-negative integer if provided
                'parking_spaces' => 'required|integer|min:0', // Parking spaces is optional but must be a non-negative integer if provided
                'resident_firstname' => 'required|string|max:255', // Resident name is required
                'resident_lastname' => 'required|string|max:255', // Resident name is required
                'resident_phone' => 'required|string|max:20', // Resident phone is optional
                'owner_firstname' => 'nullable|string|max:255', // Owner name is optional
                'owner_lastname' => 'nullable|string|max:255', // Owner name is optional
                'owner_phone' => 'nullable|string|max:20', // Owner phone is optional
            ]);

            // Create or find the resident user
            $resident = $this->findOrCreateUser(
                $request->input('resident_phone'),
                $request->input('resident_firstname'),
                $request->input('resident_lastname'),
                'resident'
            );

            // Create or find the owner user (if provided)
            $owner = null;
            if (($request->filled('owner_firstname') || $request->filled('owner_lastname')) && $request->filled('owner_phone')) {
                $owner = $this->findOrCreateUser(
                    $request->input('owner_phone'),
                    $request->input('owner_firstname'),
                    $request->input('owner_lastname'),
                    'owner'
                );
            }

            // Create the unit
            $unit = Unit::create([
                'building_id' => $request->input('building_id'),
                'unit_number' => $request->input('unit_number'),
                'type' => $request->input('type'),
                'area' => $request->input('area'),
                'floor' => $request->input('floor'),
                'number_of_rooms' => $request->input('number_of_rooms'),
                'number_of_residents' => $request->input('number_of_residents'),
                'parking_spaces' => $request->input('parking_spaces'),
            ]);

            // Attach the resident to the unit with the role 'resident'
            $unit->users()->attach($resident->id, ['role' => 'resident']);

            // Attach the owner to the unit with the role 'owner' (if provided)
            if ($owner) {
                $unit->users()->attach($owner->id, ['role' => 'owner']);
            }

            // Commit the transaction
            DB::commit();

            // Return the response with the created unit
            return $this->jsonResponseOk($unit, 'واحد با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();

            // Log the error
            \Log::error("Error creating unit: " . $e->getMessage());

            // Return an error response
            return response()->json([
                'message' => 'خطا در ایجاد واحد.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $unit = Unit::with(['building', 'images', 'users', 'residents', 'owners'])->findOrFail($id);

        return $this->jsonResponseOk($unit);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Unit $unit
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id', // Ensure the building exists
            'unit_number' => 'required|string|max:255', // Unit number is required
            'type' => 'required|in:residential,commercial', // Type must be either residential or commercial
            'area' => 'required|numeric|min:0', // Area is required and must be numeric
            'floor' => 'required|integer|min:0', // Floor is required and must be a non-negative integer
            'number_of_rooms' => 'required|integer|min:0', // Number of rooms is required and must be a non-negative integer
            'number_of_residents' => 'required|integer|min:0', // Number of residents is optional but must be a non-negative integer if provided
            'parking_spaces' => 'required|integer|min:0', // Parking spaces is optional but must be a non-negative integer if provided
            'resident_base_balance' => 'nullable|numeric|between:-9223372036854775808,9223372036854775807',
            'owner_base_balance' => 'nullable|numeric|between:-9223372036854775808,9223372036854775807',
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

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBalance(int $id): JsonResponse
    {
        $unit = Unit::select([
            'building_id',
            'unit_number',
            'resident_base_balance',
            'owner_base_balance',
            'paid_amount',
            'owner_debt',
            'resident_debt',
            'total_debt'])->findOrFail($id);

        return $this->jsonResponseOk($unit);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateBalance(Unit $unit): JsonResponse
    {
        $balanceUpdater = new InvoiceDistributionBalanceUpdater();
        $balanceUpdater->updateBalances(null, true, $unit, true);
        $unit->fresh();
        return response()->json($unit);
    }

    /**
     * Send a debt reminder SMS for a specific unit.
     *
     * @param Unit $unit
     * @return JsonResponse
     */
    public function sendDebtSMS(Unit $unit, $target_group): JsonResponse
    {
        try {
            // Validate the target group
            if (!in_array($target_group, ['resident', 'owner'])) {
                return response()->json([
                    'گروه پرداخت کننده نادرست است.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Check if the unit has any outstanding debt
            if ($target_group === 'resident' && $unit->current_resident_balance >= 0) {
                return response()->json([
                    'بدهی برای ساکن این واجد وجود ندارد.'
                ], Response::HTTP_BAD_REQUEST);
            }
            if ($target_group === 'owner' && $unit->current_owner_balance >= 0) {
                return response()->json([
                    'بدهی برای مالک این واجد وجود ندارد.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Fetch the first resident of the unit

            if ($target_group === 'resident') {
                $user = $unit->residents()->first();
            }
            if ($target_group === 'owner') {
                $user = $unit->owners()->first();
            }

            if (!$user) {
                return response()->json([
                    'ساکنی برای این واحد یافت نشد.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Convert the current date to Jalali (Shamsi) format
            $jalaliService = new JalaliService();
            $shamsiDate = $jalaliService->toJalali();

            $debtAmount = $target_group === 'resident' ? $unit->current_resident_balance : $unit->current_owner_balance;

            // Prepare the message parameters
            $parameters = [
                [
                    'name' => 'RESIDENTNAME',
                    'value' => $user->firstname . ' ' . $user->lastname,
                ],
                [
                    'name' => 'SHAMSIDATE',
                    'value' => $shamsiDate,
                ],
                [
                    'name' => 'DEBTAMOUNT',
                    'value' => number_format($debtAmount * -1),
                ],
                [
                    'name' => 'UNITID',
                    'value' => $unit->id,
                ],
                [
                    'name' => 'TARGETGROUP',
                    'value' => $target_group,
                ],
            ];

            // Send the SMS using the SmsService
            $this->smsService->sendVerificationSms(
                $user->mobile,
                '139675', // Template ID for debt reminders
                $parameters,
                $unit->id
            );

            return response()->json([
                'message' => 'Debt reminder SMS sent successfully.',
                'data' => [
                    'unit_id' => $unit->id,
                    'resident_name' => $user->firstname . ' ' . $user->lastname,
                    'mobile' => $user->mobile,
                    'total_debt' => $unit->total_debt,
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log the error
            \Log::error("Error sending debt reminder SMS for unit {$unit->id}: " . $e->getMessage());

            // Return an error response
            return response()->json([
                'error' => 'An unexpected error occurred while sending the debt reminder SMS.',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sendLoginInfo(Request $request, Unit $unit): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            // Find the unit by ID
            $user = User::findOrFail($request->input('user_id'));

            $parameters = [
                [
                    'name' => 'USERNAME',
                    'value' => $user->username
                ],
                [
                    'name' => 'PASSWORD',
                    'value' => '123456'
                ]
            ];

            // Send the SMS using the SmsService
            $this->smsService->sendVerificationSms(
                $user->mobile,
                '462598', // Template ID for login info
                $parameters,
                $unit->id
            );

            return response()->json([
                'message' => 'پیامک اطلاعات ورود به پنل با موفقیت ارسال شد.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log the error
            \Log::error("Error sending debt reminder SMS for unit {$unit->id}: " . $e->getMessage());

            // Return an error response
            return response()->json([
                'error' => 'An unexpected error occurred while sending the debt reminder SMS.',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Find or create a user based on phone number and role.
     *
     * @param string $phone
     * @param string $name
     * @param string $role
     * @return User
     */
    private function findOrCreateUser(string $phone, string $firstname, string $lastname, string $role): User
    {
        return User::firstOrCreate(
            ['phone' => $phone], // Find by phone number
            [
                'firstname' => $firstname, // Assuming the name is stored in the 'firstname' field
                'lastname' => $lastname, // Assuming the name is stored in the 'firstname' field
                'phone' => $phone,
                'role' => $role, // Assign the role (e.g., 'resident' or 'owner')
            ]
        );
    }

    /**
     * Attach a user to a unit as either a resident or owner.
     *
     * @param Request $request
     * @param int $unitId
     * @return JsonResponse
     */
    public function attachUser(Request $request, int $unitId): JsonResponse
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id', // Ensure the user exists in the database
            'role' => 'required|in:resident,owner',  // Role must be either "resident" or "owner"
        ]);

        try {
            // Find the unit by ID
            $unit = Unit::findOrFail($unitId);

            // Get the user ID and role from the request
            $userId = $request->input('user_id');
            $role = $request->input('role');

            // Check if the user is already attached to the unit with the same role
            $isAttached = $unit->users()
                ->where('user_id', $userId)
                ->wherePivot('role', $role)
                ->exists();

            if ($isAttached) {
                return response()->json([
                    'message' => 'خطا در اعتبارسنجی.',
                    'errors' => [
                        'user_id' => [
                            'کاربر قبلاً به عنوان ' . $role . ' به این واحد متصل شده است.',
                        ],
                    ],
                ], Response::HTTP_BAD_REQUEST);
            }

            // Attach the user to the unit with the specified role
            $unit->users()->attach($userId, ['role' => $role]);

            return response()->json([
                'message' => 'کاربر با موفقیت به واحد متصل شد.',
                'data' => $unit->load('users'),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error("Error attaching user to unit: " . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred while attaching the user to the unit.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Detach a user from a unit.
     *
     * @param int $unitId
     * @param int $userId
     * @return JsonResponse
     */
    public function detachUser(int $unitId, int $userId): JsonResponse
    {
        try {
            // Find the unit by ID
            $unit = Unit::findOrFail($unitId);

            // Detach the user from the unit
            $unit->users()->detach($userId);

            return response()->json([
                'message' => 'کاربر با موفقیت از واحد جدا شد.',
                'data' => $unit->load('users'), // Include the updated list of users in the response
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error("Error detaching user from unit: " . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred while detaching the user from the unit.',
            ], 500);
        }
    }
}
