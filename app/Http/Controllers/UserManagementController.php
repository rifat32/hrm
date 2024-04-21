<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Exports\UsersExport;
use App\Http\Components\AttendanceComponent;
use App\Http\Components\HolidayComponent;
use App\Http\Components\LeaveComponent;
use App\Http\Components\WorkShiftHistoryComponent;
use App\Http\Requests\AssignPermissionRequest;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\GuestUserRegisterRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Requests\MultipleFileUploadRequest;
use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Requests\UserCreateRecruitmentProcessRequest;
use App\Http\Requests\UserCreateV2Request;
use App\Http\Requests\UserStoreDetailsRequest;
use App\Http\Requests\UserUpdateAddressRequest;
use App\Http\Requests\UserUpdateBankDetailsRequest;
use App\Http\Requests\UserUpdateEmergencyContactRequest;
use App\Http\Requests\UserUpdateJoiningDateRequest;
use App\Http\Requests\UserUpdateProfileRequest;
use App\Http\Requests\UserUpdateRecruitmentProcessRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UserUpdateV2Request;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\UserActivityUtil;
use App\Http\Utils\UserDetailsUtil;
use App\Mail\SendOriginalPassword;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Business;
use App\Models\Department;
use App\Models\EmployeeAddressHistory;



use App\Models\WorkShiftHistory;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveRecord;

use App\Models\Role;
use App\Models\SettingLeaveType;
use App\Models\User;
use App\Models\UserRecruitmentProcess;
use Carbon\Carbon;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\File;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

use App\Mail\SendPassword;
use App\Models\SettingLeave;
use App\Models\UserAssetHistory;
use Illuminate\Support\Facades\Mail;

// eeeeee
class UserManagementController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, ModuleUtil, UserDetailsUtil;

    protected $workShiftHistoryComponent;
    protected $holidayComponent;
    protected $leaveComponent;
    protected $attendanceComponent;

    public function __construct(WorkShiftHistoryComponent $workShiftHistoryComponent, HolidayComponent $holidayComponent,  LeaveComponent $leaveComponent, AttendanceComponent $attendanceComponent)
    {

        $this->workShiftHistoryComponent = $workShiftHistoryComponent;
        $this->holidayComponent = $holidayComponent;
        $this->leaveComponent = $leaveComponent;
        $this->attendanceComponent = $attendanceComponent;
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/users/single-file-upload",
     *      operationId="createUserFileSingle",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user file ",
     *      description="This method is to store user file",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"file"},
     *         @OA\Property(
     *             description="file to upload",
     *             property="file",
     *             type="file",
     *             collectionFormat="multi",
     *         )
     *     )
     * )



     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function createUserFileSingle(SingleFileUploadRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            // if(!$request->user()->hasPermissionTo('business_create')){
            //      return response()->json([
            //         "message" => "You can not perform this action"
            //      ],401);
            // }

            $request_data = $request->validated();

            $location =  config("setup-config.user_files_location");

            $new_file_name = time() . '_' . str_replace(' ', '_', $request_data["file"]->getClientOriginalName());

            $request_data["file"]->move(public_path($location), $new_file_name);


            return response()->json(["file" => $new_file_name, "location" => $location, "full_location" => ("/" . $location . "/" . $new_file_name)], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/users/multiple-file-upload",
     *      operationId="createUserFileMultiple",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store multiple user files",
     *      description="This method is to store multiple user files",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"files[]"},
     *         @OA\Property(
     *             description="array of files to upload",
     *             property="files[]",
     *             type="array",
     *             @OA\Items(
     *                 type="file"
     *             ),
     *             collectionFormat="multi",
     *         )
     *     )
     * )



     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function createUserFileMultiple(MultipleFileUploadRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            $insertableData = $request->validated();

            $location =  config("setup-config.user_files_location");

            $files = [];
            if (!empty($insertableData["files"])) {
                foreach ($insertableData["files"] as $file) {
                    $new_file_name = time() . '_' . $file->getClientOriginalName();
                    $new_file_name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                    $file->move(public_path($location), $new_file_name);

                    array_push($files, ("/" . $location . "/" . $new_file_name));
                }
            }


            return response()->json(["files" => $files], 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-image",
     *      operationId="createUserImage",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user image ",
     *      description="This method is to store user image",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"image"},
     *         @OA\Property(
     *             description="image to upload",
     *             property="image",
     *             type="file",
     *             collectionFormat="multi",
     *         )
     *     )
     * )



     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function createUserImage(ImageUploadRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            // if(!$request->user()->hasPermissionTo('user_create')){
            //      return response()->json([
            //         "message" => "You can not perform this action"
            //      ],401);
            // }

            $request_data = $request->validated();

            $location =  config("setup-config.user_image_location");

            $new_file_name = time() . '_' . str_replace(' ', '_', $request_data["image"]->getClientOriginalName());

            $request_data["image"]->move(public_path($location), $new_file_name);


            return response()->json(["image" => $new_file_name, "location" => $location, "full_location" => ("/" . $location . "/" . $new_file_name)], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    function generate_unique_username($firstName, $middleName, $lastName, $business_id = null)
    {
        $baseUsername = $firstName . "." . ($middleName ? $middleName . "." : "") . $lastName;
        $username = $baseUsername;
        $counter = 1;

        // Check if the generated username is already in use within the specified business
        while (User::where('user_name', $username)->where('business_id', $business_id)->exists()) {
            // If the username exists, append a counter to make it unique
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/users",
     *      operationId="createUser",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user",
     *      description="This method is to store user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *             @OA\Property(property="first_Name", type="string", format="string",example="Rifat"),
     *      *            @OA\Property(property="middle_Name", type="string", format="string",example="Al"),
     *      *      *            @OA\Property(property="NI_number", type="string", format="string",example="drtjdjdj"),
     *
     *
     *            @OA\Property(property="last_Name", type="string", format="string",example="Al"),
     *
     *
     *              @OA\Property(property="gender", type="string", format="string",example="male"),
     *                @OA\Property(property="is_in_employee", type="boolean", format="boolean",example="1"),
     *               @OA\Property(property="designation_id", type="number", format="number",example="1"),

     *               @OA\Property(property="salary_per_annum", type="string", format="string",example="10"),
     *  *            @OA\Property(property="weekly_contractual_hours", type="string", format="string",example="10"),
     *               @OA\Property(property="minimum_working_days_per_week", type="string", format="string",example="5"),
     *     @OA\Property(property="overtime_rate", type="string", format="string",example="5"),
     *
     *
     *     @OA\Property(property="joining_date", type="string", format="date", example="2023-11-16"),
     *
     *            @OA\Property(property="email", type="string", format="string",example="rifatalashwad0@gmail.com"),
     *    *            @OA\Property(property="image", type="string", format="string",example="...png"),

     * *  @OA\Property(property="password", type="string", format="boolean",example="12345678"),
     *  * *  @OA\Property(property="password_confirmation", type="string", format="boolean",example="12345678"),
     *  * *  @OA\Property(property="phone", type="string", format="boolean",example="01771034383"),
     *  * *  @OA\Property(property="address_line_1", type="string", format="boolean",example="dhaka"),
     *  * *  @OA\Property(property="address_line_2", type="string", format="boolean",example="dinajpur"),
     *  * *  @OA\Property(property="country", type="string", format="boolean",example="Bangladesh"),
     *  * *  @OA\Property(property="city", type="string", format="boolean",example="Dhaka"),
     *  * *  @OA\Property(property="postcode", type="string", format="boolean",example="1207"),
     *     *  * *  @OA\Property(property="lat", type="string", format="boolean",example="1207"),
     *     *  * *  @OA\Property(property="long", type="string", format="boolean",example="1207"),
     *  *  * *  @OA\Property(property="role", type="string", format="boolean",example="customer"),

     *
     * *   @OA\Property(property="emergency_contact_details", type="string", format="array", example={})
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function createUser(UserCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id = $request->user()->business_id;

            $request_data = $request->validated();

            return      DB::transaction(function () use ($request_data) {
                if (!auth()->user()->hasRole('superadmin') && $request_data["role"] == "superadmin") {
                    $this->storeError(
                        "You can not create superadmin.",
                        403,
                        "front end error",
                        "front end error"
                    );
                    $error =  [
                        "message" => "You can not create superadmin.",
                    ];
                    throw new Exception(json_encode($error), 403);
                }


                $request_data['password'] = Hash::make($request_data['password']);
                $request_data['is_active'] = true;
                $request_data['remember_token'] = Str::random(10);


                if (!empty($business_id)) {
                    $request_data['business_id'] = $business_id;
                }


                $user =  User::create($request_data);
                $username = $this->generate_unique_username($user->first_Name, $user->middle_Name, $user->last_Name, $user->business_id);
                $user->user_name = $username;
                $user->email_verified_at = now();
                $user->save();
                $user->assignRole($request_data['role']);
                $user->roles = $user->roles->pluck('name');
                return response($user, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Post(
     *      path="/v2.0/users",
     *      operationId="createUserV2",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user",
     *      description="This method is to store user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *             @OA\Property(property="first_Name", type="string", format="string",example="Rifat"),
     *      *            @OA\Property(property="middle_Name", type="string", format="string",example="Al"),
     *     *      *      *            @OA\Property(property="NI_number", type="string", format="string",example="drtjdjdj"),
     *
     *            @OA\Property(property="last_Name", type="string", format="string",example="Al"),
     * *            @OA\Property(property="user_id", type="string", format="string",example="045674"),
     *
     *
     *              @OA\Property(property="gender", type="string", format="string",example="male"),
     *                @OA\Property(property="is_in_employee", type="boolean", format="boolean",example="1"),
     *               @OA\Property(property="designation_id", type="number", format="number",example="1"),
     *              @OA\Property(property="employment_status_id", type="number", format="number",example="1"),
     *               @OA\Property(property="salary_per_annum", type="string", format="string",example="10"),
     *  *  *               @OA\Property(property="weekly_contractual_hours", type="string", format="string",example="10"),
     * *  *  *               @OA\Property(property="minimum_working_days_per_week", type="string", format="string",example="5"),
     *   *     @OA\Property(property="overtime_rate", type="string", format="string",example="5"),
     *
     *     @OA\Property(property="joining_date", type="string", format="date", example="2023-11-16"),
     *
     *            @OA\Property(property="email", type="string", format="string",example="rifatalashwad0@gmail.com"),
     *    *            @OA\Property(property="image", type="string", format="string",example="...png"),

     * *  @OA\Property(property="password", type="string", format="boolean",example="12345678"),
     *  * *  @OA\Property(property="password_confirmation", type="string", format="boolean",example="12345678"),
     *  * *  @OA\Property(property="phone", type="string", format="boolean",example="01771034383"),
     *  * *  @OA\Property(property="address_line_1", type="string", format="boolean",example="dhaka"),
     *  * *  @OA\Property(property="address_line_2", type="string", format="boolean",example="dinajpur"),
     *  * *  @OA\Property(property="country", type="string", format="boolean",example="Bangladesh"),
     *  * *  @OA\Property(property="city", type="string", format="boolean",example="Dhaka"),
     *  * *  @OA\Property(property="postcode", type="string", format="boolean",example="1207"),
     *     *  * *  @OA\Property(property="lat", type="string", format="boolean",example="1207"),
     *     *  * *  @OA\Property(property="long", type="string", format="boolean",example="1207"),
     *  *  * *  @OA\Property(property="role", type="string", format="boolean",example="customer"),
     *      *  *  * *  @OA\Property(property="work_shift_id", type="number", format="number",example="1"),
     *  *     @OA\Property(property="work_location_id", type="integer", format="int", example="1"),
     *
     *
     *
     * @OA\Property(property="recruitment_processes", type="string", format="array", example={
     * {
     * "recruitment_process_id":1,
     * "description":"description",
     * "attachments":{"/abcd.jpg","/efgh.jpg"}
     * },
     *      * {
     * "recruitment_process_id":1,
     * "description":"description",
     * "attachments":{"/abcd.jpg","/efgh.jpg"}
     * }
     *
     *
     *
     * }),
     *
     *      *  * @OA\Property(property="departments", type="string", format="array", example={1,2,3}),
     *
     * *   @OA\Property(property="emergency_contact_details", type="string", format="array", example={}),
     *
     *  *  * *  @OA\Property(property="immigration_status", type="string", format="string",example="british_citizen"),
     *         @OA\Property(property="is_active_visa_details", type="boolean", format="boolean",example="1"),
     *  *         @OA\Property(property="is_active_right_to_works", type="boolean", format="boolean",example="1"),
     *

     *     @OA\Property(property="sponsorship_details", type="string", format="string", example={
     *    "date_assigned": "2023-01-01",
     *    "expiry_date": "2024-01-01",
     *    "status": "pending",
     *  *    "note": "pending",
     *  *    "certificate_number": "pending note",
     *  *    "current_certificate_status": "pending",
     * *  *    "is_sponsorship_withdrawn": 1
     * }),
     *
     * *
     * *
     * *
     * *
     * *
     * *
     *
     *
     *       @OA\Property(property="visa_details", type="string", format="array", example={
     *      "BRP_number": "BRP123",
     *      "visa_issue_date": "2023-01-01",
     *      "visa_expiry_date": "2024-01-01",
     *      "place_of_issue": "City",
     *      "visa_docs": {
     *        {
     *          "file_name": "document1.pdf",
     *          "description": "Description 1"
     *        },
     *        {
     *  *          "file_name": "document2.pdf",
     *          "description": "Description 2"
     *        }
     *      }
     *
     * }
     * ),
     * *
     * @OA\Property(
     *     property="right_to_works",
     *     type="string",
     *     format="string",
     *     example={
     *         "right_to_work_code": "Code123",
     *         "right_to_work_check_date": "2023-01-01",
     *         "right_to_work_expiry_date": "2024-01-01",
     *         "right_to_work_docs": {
     *             {
     *                 "file_name": "document1.pdf",
     *                 "description": "Description 1"
     *             },
     *             {
     *                 "file_name": "document2.pdf",
     *                 "description": "Description 2"
     *             }
     *         }
     *     }
     * ),

     *
     *
     *
     *
     *
     *
     *
     *  *     @OA\Property(property="passport_details", type="string", format="string", example={
     *    "passport_number": "ABC123",
     *    "passport_issue_date": "2023-01-01",
     *    "passport_expiry_date": "2024-01-01",
     *    "place_of_issue": "City"
     *
     * })
     *
     *
     *

     *
     *
     *
     *
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function createUserV2(UserCreateV2Request $request)
    {
        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('user_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id = $request->user()->business_id;

            $request_data = $request->validated();

            if (!$request->user()->hasRole('superadmin') && $request_data["role"] == "superadmin") {
                $this->storeError(
                    "You can not create superadmin.",
                    403,
                    "front end error",
                    "front end error"
                );
                $error =  [
                    "message" => "You can not create superadmin.",
                ];
                throw new Exception(json_encode($error), 403);
            }

            // $request_data['password'] = Hash::make($request['password']);

            $password = Str::random(11);
            $request_data['password'] = Hash::make($password);




            $request_data['is_active'] = true;
            $request_data['remember_token'] = Str::random(10);


            if (!empty($business_id)) {
                $request_data['business_id'] = $business_id;
            }


            $user =  User::create($request_data);
            $username = $this->generate_unique_username($user->first_Name, $user->middle_Name, $user->last_Name, $user->business_id);
            $user->user_name = $username;
            $token = Str::random(30);
            $user->resetPasswordToken = $token;
            $user->resetPasswordExpires = Carbon::now()->subDays(-1);
            $user->pension_eligible = 0;
            $user->save();
            $this->delete_old_histories();
            $user->departments()->sync($request_data['departments']);
            $user->assignRole($request_data['role']);


            $this->store_work_shift($request_data, $user);
            $this->store_project($request_data, $user);
            $this->store_pension($request_data, $user);
            $this->store_recruitment_processes($request_data, $user);

            if (in_array($request["immigration_status"], ['sponsored'])) {
                $this->store_sponsorship_details($request_data, $user);
            }
            if (in_array($request["immigration_status"], ['immigrant', 'sponsored'])) {
                $this->store_passport_details($request_data, $user);
                $this->store_visa_details($request_data, $user);
            }
            if (in_array($request["immigration_status"], ['ilr', 'immigrant', 'sponsored'])) {
                $this->store_right_to_works($request_data, $user);
            }
            $user->roles = $user->roles->pluck('name');

            if (env("SEND_EMAIL") == true) {
                Mail::to($user->email)->send(new SendOriginalPassword($user, $password));
            }

            DB::commit();
            return response($user, 201);
        } catch (Exception $e) {
            DB::rollBack();

            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Put(
     *      path="/v1.0/users",
     *      operationId="updateUser",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user",
     *      description="This method is to update user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *             @OA\Property(property="first_Name", type="string", format="string",example="Rifat"),
     *   *            @OA\Property(property="middle_Name", type="string", format="string",example="How was this?"),
     *     *      *      *            @OA\Property(property="NI_number", type="string", format="string",example="drtjdjdj"),
     *            @OA\Property(property="last_Name", type="string", format="string",example="How was this?"),
     *
     *
     *      * *            @OA\Property(property="user_id", type="string", format="string",example="045674"),
     *            @OA\Property(property="email", type="string", format="string",example="How was this?"),
     *    *    *            @OA\Property(property="image", type="string", format="string",example="...png"),
     *                @OA\Property(property="gender", type="string", format="string",example="male"),
     *                @OA\Property(property="is_in_employee", type="boolean", format="boolean",example="1"),
     *               @OA\Property(property="designation_id", type="number", format="number",example="1"),
     *              @OA\Property(property="employment_status_id", type="number", format="number",example="1"),
     *               @OA\Property(property="salary_per_annum", type="string", format="string",example="10"),
     *           @OA\Property(property="weekly_contractual_hours", type="string", format="string",example="10"),
     *     *           @OA\Property(property="minimum_working_days_per_week", type="string", format="string",example="10"),
     *   *     @OA\Property(property="overtime_rate", type="string", format="string",example="5"),
     *
     *     @OA\Property(property="joining_date", type="string", format="date", example="2023-11-16"),

     * *  @OA\Property(property="password", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="password_confirmation", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="phone", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="address_line_1", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="address_line_2", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="country", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="city", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="postcode", type="boolean", format="boolean",example="1"),
     *     *     *  * *  @OA\Property(property="lat", type="string", format="boolean",example="1207"),
     *     *  * *  @OA\Property(property="long", type="string", format="boolean",example="1207"),
     *  *  * *  @OA\Property(property="role", type="boolean", format="boolean",example="customer"),
     *      *      *  *  * *  @OA\Property(property="work_shift_id", type="number", format="number",example="1"),
     *      *  * @OA\Property(property="departments", type="string", format="array", example={1,2,3}),
     * * *   @OA\Property(property="emergency_contact_details", type="string", format="array", example={})
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function updateUser(UserUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();



            $userQuery = User::where([
                "id" => $request["id"]
            ]);
            $updatableUser = $userQuery->first();
            if ($updatableUser->hasRole("superadmin") && $request["role"] != "superadmin") {
                return response()->json([
                    "message" => "You can not change the role of super admin"
                ], 401);
            }
            if (!$request->user()->hasRole('superadmin') && $updatableUser->business_id != $request->user()->business_id && $updatableUser->created_by != $request->user()->id) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }


            if (!empty($request_data['password'])) {
                $request_data['password'] = Hash::make($request_data['password']);
            } else {
                unset($request_data['password']);
            }
            $request_data['is_active'] = true;
            $request_data['remember_token'] = Str::random(10);
            $userQueryTerms = [
                "id" => $request_data["id"],
            ];


            $user = User::where($userQueryTerms)->first();

            if ($user) {
                $user->fill(collect($request_data)->only([
                    'first_Name',
                    'middle_Name',
                    'NI_number',
                    'last_Name',
                    "email",
                    'user_id',
                    'password',
                    'phone',
                    'address_line_1',
                    'address_line_2',
                    'country',
                    'city',
                    'postcode',
                    "lat",
                    "long",
                    "image",
                    'gender',
                    'is_in_employee',
                    'designation_id',
                    'employment_status_id',
                    'joining_date',
                    'emergency_contact_details',
                    'salary_per_annum',
                    'weekly_contractual_hours',
                    'minimum_working_days_per_week',
                    'overtime_rate',
                ])->toArray());

                $user->save();
            }
            if (!$user) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }
            $user->syncRoles([$request_data['role']]);



            $user->roles = $user->roles->pluck('name');


            return response($user, 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/users/assign-roles",
     *      operationId="assignUserRole",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user",
     *      description="This method is to update user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *
     *  *  * *  @OA\Property(property="roles", type="string", format="array",example={"business_owner#1","business_admin#1"})

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function assignUserRole(AssignRoleRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();



            $userQuery = User::where([
                "id" => $request["id"]
            ]);
            $user = $userQuery->first();

            if (!$user) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }


            foreach ($request_data["roles"] as $role) {
                if ($user->hasRole("superadmin") && $role != "superadmin") {
                    return response()->json([
                        "message" => "You can not change the role of super admin"
                    ], 401);
                }
                if (!$request->user()->hasRole('superadmin') && $user->business_id != $request->user()->business_id && $user->created_by != $request->user()->id) {
                    return response()->json([
                        "message" => "You can not update this user"
                    ], 401);
                }
            }

            $roles = Role::whereIn('name', $request_data["roles"])->get();

            $user->syncRoles($roles);



            $user->roles = $user->roles->pluck('name');


            return response($user, 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Put(
     *      path="/v1.0/users/assign-permissions",
     *      operationId="assignUserPermission",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user",
     *      description="This method is to update user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *
     *  *  * *  @OA\Property(property="permissions", type="string", format="array",example={"business_owner","business_admin"})

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function assignUserPermission(AssignPermissionRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasRole('superadmin')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();



            $userQuery = User::where([
                "id" => $request["id"]
            ]);
            $user = $userQuery->first();

            if (!$user) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }


            foreach ($request_data["permissions"] as $role) {
                if ($user->hasRole("superadmin") && $role != "superadmin") {
                    return response()->json([
                        "message" => "You can not change the role of super admin"
                    ], 401);
                }
                if (!$request->user()->hasRole('superadmin') && $user->business_id != $request->user()->business_id && $user->created_by != $request->user()->id) {
                    return response()->json([
                        "message" => "You can not update this user"
                    ], 401);
                }
            }


            $permissions = Permission::whereIn('name', $request_data["permissions"])->get();
            $user->givePermissionTo($permissions);



            return response($user, 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v2.0/users",
     *      operationId="updateUserV2",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user",
     *      description="This method is to update user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *             @OA\Property(property="first_Name", type="string", format="string",example="Rifat"),
     *   *            @OA\Property(property="middle_Name", type="string", format="string",example="How was this?"),
     *     *      *      *            @OA\Property(property="NI_number", type="string", format="string",example="drtjdjdj"),
     *            @OA\Property(property="last_Name", type="string", format="string",example="How was this?"),
     *
     *
     *      * *            @OA\Property(property="user_id", type="string", format="string",example="045674"),
     *            @OA\Property(property="email", type="string", format="string",example="How was this?"),
     *    *    *            @OA\Property(property="image", type="string", format="string",example="...png"),
     *                @OA\Property(property="gender", type="string", format="string",example="male"),
     *                @OA\Property(property="is_in_employee", type="boolean", format="boolean",example="1"),
     *               @OA\Property(property="designation_id", type="number", format="number",example="1"),
     *              @OA\Property(property="employment_status_id", type="number", format="number",example="1"),
     *               @OA\Property(property="salary_per_annum", type="string", format="string",example="10"),
     *           @OA\Property(property="weekly_contractual_hours", type="string", format="string",example="10"),
     *      *           @OA\Property(property="minimum_working_days_per_week", type="string", format="string",example="5"),
     *   *     @OA\Property(property="overtime_rate", type="string", format="string",example="5"),
     *
     *     @OA\Property(property="joining_date", type="string", format="date", example="2023-11-16"),

     * *  @OA\Property(property="password", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="password_confirmation", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="phone", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="address_line_1", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="address_line_2", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="country", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="city", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="postcode", type="boolean", format="boolean",example="1"),
     *     *     *  * *  @OA\Property(property="lat", type="string", format="boolean",example="1207"),
     *     *  * *  @OA\Property(property="long", type="string", format="boolean",example="1207"),
     *  *  * *  @OA\Property(property="role", type="boolean", format="boolean",example="customer"),
     *      *      *  *  * *  @OA\Property(property="work_shift_id", type="number", format="number",example="1"),
     *  *     @OA\Property(property="work_location_id", type="integer", format="int", example="1"),
     * *         @OA\Property(property="is_active_visa_details", type="boolean", format="boolean",example="1"),
     *  * *         @OA\Property(property="is_active_right_to_works", type="boolean", format="boolean",example="1"),
     *     * @OA\Property(property="recruitment_processes", type="string", format="array", example={
     * {
     * "recruitment_process_id":1,
     * "description":"description",
     * "attachments":{"/abcd.jpg","/efgh.jpg"}
     * },
     *      * {
     * "recruitment_process_id":1,
     * "description":"description",
     * "attachments":{"/abcd.jpg","/efgh.jpg"}
     * }
     *
     *
     *
     * }),
     *      *  * @OA\Property(property="departments", type="string", format="array", example={1,2,3}),
     * * *   @OA\Property(property="emergency_contact_details", type="string", format="array", example={})
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function updateUserV2(UserUpdateV2Request $request)
    {
        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");


            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();
            $userQuery = User::where([
                "id" => $request["id"]
            ]);
            $updatableUser = $userQuery->first();
            if ($updatableUser->hasRole("superadmin") && $request["role"] != "superadmin") {
                return response()->json([
                    "message" => "You can not change the role of super admin"
                ], 401);
            }
            if (!$request->user()->hasRole('superadmin') && $updatableUser->business_id != $request->user()->business_id && $updatableUser->created_by != $request->user()->id) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }


            if (!empty($request_data['password'])) {
                $request_data['password'] = Hash::make($request_data['password']);
            } else {
                unset($request_data['password']);
            }
            $request_data['is_active'] = true;
            $request_data['remember_token'] = Str::random(10);





            $userQueryTerms = [
                "id" => $request_data["id"],
            ];


            $user = User::where($userQueryTerms)->first();

            if ($user) {
                $user->fill(collect($request_data)->only([
                    'first_Name',
                    'last_Name',
                    'middle_Name',
                    "NI_number",

                    "email",
                    "color_theme_name",
                    'emergency_contact_details',
                    'gender',
                    'is_in_employee',
                    'designation_id',
                    'employment_status_id',
                    'joining_date',
                    "date_of_birth",
                    'salary_per_annum',
                    'weekly_contractual_hours',
                    'minimum_working_days_per_week',
                    'overtime_rate',
                    'phone',
                    'image',
                    'address_line_1',
                    'address_line_2',
                    'country',
                    'city',
                    'postcode',
                    "lat",
                    "long",
                    'is_active_visa_details',
                    "is_active_right_to_works",
                    'is_sponsorship_offered',

                    "immigration_status",
                    'work_location_id',

                ])->toArray());

                $user->save();
            }
            if (!$user) {

                return response()->json([
                    "message" => "no user found"
                ], 404);
            }

            $this->delete_old_histories();


            $user->departments()->sync($request_data['departments']);
            $user->syncRoles([$request_data['role']]);

            $this->update_work_shift($request_data, $user);
            $this->update_address_history($request_data, $user);
            $this->update_recruitment_processes($request_data, $user);



            if (in_array($request["immigration_status"], ['sponsored'])) {
                $this->update_sponsorship($request_data, $user);
            }


            if (in_array($request["immigration_status"], ['immigrant', 'sponsored'])) {
                $this->update_passport_details($request_data, $user);
                $this->update_visa_details($request_data, $user);
            }

            if (in_array($request["immigration_status"], ['ilr', 'immigrant', 'sponsored'])) {
                $this->update_right_to_works($request_data, $user);
            }

            $user->roles = $user->roles->pluck('name');
            DB::commit();
            return response($user, 201);
        } catch (Exception $e) {
            DB::rollBack();


            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Put(
     *      path="/v1.0/users/update-address",
     *      operationId="updateUserAddress",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user address",
     *      description="This method is to update user address",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *           @OA\Property(property="id", type="string", format="number",example="1"),

     *

     *  * *  @OA\Property(property="phone", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="address_line_1", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="address_line_2", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="country", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="city", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="postcode", type="boolean", format="boolean",example="1"),
     *     *     *  * *  @OA\Property(property="lat", type="string", format="boolean",example="1207"),
     *     *  * *  @OA\Property(property="long", type="string", format="boolean",example="1207"),

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function updateUserAddress(UserUpdateAddressRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();



            $userQuery = User::where([
                "id" => $request["id"]
            ]);
            $updatableUser = $userQuery->first();
            if ($updatableUser->hasRole("superadmin") && $request["role"] != "superadmin") {
                return response()->json([
                    "message" => "You can not change the role of super admin"
                ], 401);
            }
            if (!$request->user()->hasRole('superadmin') && $updatableUser->business_id != $request->user()->business_id && $updatableUser->created_by != $request->user()->id) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }



            $user_query  = User::where([
                "id" => $request_data["id"],
            ]);




            $user  =  tap($user_query)->update(
                collect($request_data)->only([
                    'phone',
                    'address_line_1',
                    'address_line_2',
                    'country',
                    'city',
                    'postcode',
                    'lat',
                    'long',

                ])->toArray()
            )
                // ->with("somthing")
                ->first();
            if (!$user) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }

            // history section

            $address_history_data = [
                'user_id' => $user->id,
                'from_date' => now(),
                'created_by' => $request->user()->id,
                'address_line_1' => $request_data["address_line_1"],
                'address_line_2' => $request_data["address_line_2"],
                'country' => $request_data["country"],
                'city' => $request_data["city"],
                'postcode' => $request_data["postcode"],
                'lat' => $request_data["lat"],
                'long' => $request_data["long"]
            ];

            $employee_address_history  =  EmployeeAddressHistory::where([
                "user_id" =>   $updatableUser->id,
                "to_date" => NULL
            ])
                ->latest('created_at')
                ->first();

            if ($employee_address_history) {
                $fields_to_check = ["address_line_1", "address_line_2", "country", "city", "postcode"];


                $fields_changed = false; // Initialize to false
                foreach ($fields_to_check as $field) {
                    $value1 = $employee_address_history->$field;
                    $value2 = $request_data[$field];

                    if ($value1 !== $value2) {
                        $fields_changed = true;
                        break;
                    }
                }





                if (
                    $fields_changed
                ) {
                    $employee_address_history->to_date = now();
                    $employee_address_history->save();
                    EmployeeAddressHistory::create($address_history_data);
                }
            } else {
                EmployeeAddressHistory::create($address_history_data);
            }

            // end history section


            return response($user, 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Put(
     *      path="/v1.0/users/update-bank-details",
     *      operationId="updateUserBankDetails",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user address",
     *      description="This method is to update user address",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *  * *  @OA\Property(property="bank_id", type="number", format="number",example="1"),
     *  * *  @OA\Property(property="sort_code", type="string", format="string",example="sort_code"),
     *  * *  @OA\Property(property="account_number", type="string", format="string",example="account_number"),
     *  * *  @OA\Property(property="account_name", type="string", format="string",example="account_name")
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function updateUserBankDetails(UserUpdateBankDetailsRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();



            $userQuery = User::where([
                "id" => $request["id"]
            ]);
            $updatableUser = $userQuery->first();
            if ($updatableUser->hasRole("superadmin") && $request["role"] != "superadmin") {
                return response()->json([
                    "message" => "You can not change the role of super admin"
                ], 401);
            }
            if (!$request->user()->hasRole('superadmin') && $updatableUser->business_id != $request->user()->business_id && $updatableUser->created_by != $request->user()->id) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }



            $user_query  = User::where([
                "id" => $request_data["id"],
            ]);




            $user  =  tap($user_query)->update(
                collect($request_data)->only([
                    'bank_id',
                    'sort_code',
                    'account_number',
                    'account_name',
                ])->toArray()
            )
                // ->with("somthing")
                ->first();
            if (!$user) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }








            return response($user, 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/users/update-joining-date",
     *      operationId="updateUserJoiningDate",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user address",
     *      description="This method is to update user address",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *  @OA\Property(property="id", type="string", format="number",example="1"),
     *  @OA\Property(property="joining_date", type="string", format="string",example="joining_date")
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function updateUserJoiningDate(UserUpdateJoiningDateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $userQuery = User::where([
                "id" => $request["id"]
            ]);

            $updatableUser = $userQuery->first();
            if ($updatableUser->hasRole("superadmin") && $request["role"] != "superadmin") {
                return response()->json([
                    "message" => "You can not change the role of super admin"
                ], 401);
            }

            if (!$request->user()->hasRole('superadmin') && $updatableUser->business_id != $request->user()->business_id && $updatableUser->created_by != $request->user()->id) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }

            $user_query  = User::where([
                "id" => $request_data["id"],
            ]);





    $attendance_exists = Attendance::where(
  "in_date" , "<" ,$request_data["joining_date"],
)
->where([
    "user_id" => $request_data["id"]
])->exists();

           if($attendance_exists) {
             return response()->json([
               "message" => "Attendance exists before " . $request_data["joining_date"]
             ],401);
        }

        $leave_exists = Leave::where(
            "start_date" , "<" ,$request_data["joining_date"],
          )
          ->where([
              "user_id" => $request_data["id"]
          ])->exists();

         if($leave_exists) {
          return response()->json([
                         "message" => "Leave exists before " . $request_data["joining_date"]
                       ],401);
                  }

                  $asset_assigned = UserAssetHistory::where(
                    "from_date" , "<" ,$request_data["joining_date"],
                  )
                  ->where([
                      "user_id" => $request_data["id"]
                  ])->exists();

                 if($asset_assigned) {
                               return response()->json([
                                 "message" => "Asset assigned before " . $request_data["joining_date"]
                               ],401);
                }



            $user = tap($user_query)->update(
                collect($request_data)->only([
                    'joining_date'
                ])->toArray()
            )
                // ->with("somthing")
                ->first();


            if (!$user) {
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }



            return response($user, 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/users/update-emergency-contact",
     *      operationId="updateEmergencyContact",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user contact",
     *      description="This method is to update contact",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *           @OA\Property(property="id", type="string", format="number",example="1"),

     * * *   @OA\Property(property="emergency_contact_details", type="string", format="array", example={})

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function updateEmergencyContact(UserUpdateEmergencyContactRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();



            $userQuery = User::where([
                "id" => $request["id"]
            ]);
            $updatableUser = $userQuery->first();
            if ($updatableUser->hasRole("superadmin") && $request["role"] != "superadmin") {
                return response()->json([
                    "message" => "You can not change the role of super admin"
                ], 401);
            }
            if (!$request->user()->hasRole('superadmin') && $updatableUser->business_id != $request->user()->business_id && $updatableUser->created_by != $request->user()->id) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }



            $userQueryTerms = [
                "id" => $request_data["id"],
            ];

            $user  =  tap(User::where($userQueryTerms))->update(
                collect($request_data)->only([
                    'emergency_contact_details'

                ])->toArray()
            )
                // ->with("somthing")

                ->first();
            if (!$user) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }



            return response($user, 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/users/toggle-active",
     *      operationId="toggleActiveUser",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle user activity",
     *      description="This method is to toggle user activity",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function toggleActiveUser(GetIdRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $userQuery  = User::where(["id" => $request_data["id"]]);
            if (!auth()->user()->hasRole('superadmin')) {
                $userQuery = $userQuery->where(function ($query) {
                    $query->where('business_id', auth()->user()->business_id)
                        ->orWhere('created_by', auth()->user()->id)
                        ->orWhere('id', auth()->user()->id);
                });
            }

            $user =  $userQuery->first();
            if (!$user) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }
            if ($user->hasRole("superadmin")) {
                return response()->json([
                    "message" => "superadmin can not be deactivated"
                ], 401);
            }

            $user->update([
                'is_active' => !$user->is_active
            ]);

            return response()->json(['message' => 'User status updated successfully'], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/users/profile",
     *      operationId="updateUserProfile",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user profile",
     *      description="This method is to update user profile",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *             @OA\Property(property="first_Name", type="string", format="string",example="Rifat"),
     *            @OA\Property(property="last_Name", type="string", format="string",example="How was this?"),
     *            @OA\Property(property="email", type="string", format="string",example="How was this?"),

     * *  @OA\Property(property="password", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="password_confirmation", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="phone", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="address_line_1", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="address_line_2", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="country", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="city", type="boolean", format="boolean",example="1"),
     *  * *  @OA\Property(property="postcode", type="boolean", format="boolean",example="1"),
     *     *     *  * *  @OA\Property(property="lat", type="string", format="boolean",example="1207"),
     *     *  * *  @OA\Property(property="long", type="string", format="boolean",example="1207"),
     * * *   @OA\Property(property="emergency_contact_details", type="string", format="array", example={})

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function updateUserProfile(UserUpdateProfileRequest $request)
    {

        try {

            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            $request_data = $request->validated();


            if (!empty($request_data['password'])) {
                $request_data['password'] = Hash::make($request_data['password']);
            } else {
                unset($request_data['password']);
            }


            $userQuery = User::where([
                "id" => $request["id"]
            ]);
            $updatableUser = $userQuery->first();
            //  $request_data['is_active'] = true;
            //  $request_data['remember_token'] = Str::random(10);
            $user  =  tap(User::where(["id" => $request->user()->id]))->update(
                collect($request_data)->only([
                    'first_Name',
                    'middle_Name',

                    'last_Name',
                    'password',
                    'phone',
                    'address_line_1',
                    'address_line_2',
                    'country',
                    'city',
                    'postcode',
                    "lat",
                    "long",
                    "image",
                    "gender",
                    'emergency_contact_details',

                ])->toArray()
            )
                // ->with("somthing")

                ->first();

            if (!$user) {

                return response()->json([
                    "message" => "no user found"
                ], 404);
            }

            // history section
            $this->update_address_history($request_data, $user);
            // end history section



            $user->roles = $user->roles->pluck('name');


            return response($user, 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/users",
     *      operationId="getUsers",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *              @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="response_type: in pdf,csv,json",
     *         required=true,
     *  example="json"
     *      ),
     *      *   *              @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),
     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     *
     *
     * @OA\Parameter(
     * name="full_name",
     * in="query",
     * description="full_name",
     * required=true,
     * example="full_name"
     * ),
     *
     *    * @OA\Parameter(
     * name="employee_id",
     * in="query",
     * description="employee_id",
     * required=true,
     * example="1"
     * ),
     *
     *  *
     *    * @OA\Parameter(
     * name="email",
     * in="query",
     * description="email",
     * required=true,
     * example="email"
     * ),
     *
     *
     *
     *
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *   * *  @OA\Parameter(
     * name="is_in_employee",
     * in="query",
     * description="is_in_employee",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="is_in_employee",
     * in="query",
     * description="is_in_employee",
     * required=true,
     * example="1"
     * ),
     *  * @OA\Parameter(
     * name="designation_id",
     * in="query",
     * description="designation_id",
     * required=true,
     * example="1"
     * ),
     *    *  * @OA\Parameter(
     * name="work_location_id",
     * in="query",
     * description="work_location_id",
     * required=true,
     * example="1"
     * ),
     *     *    *  * @OA\Parameter(
     * name="holiday_id",
     * in="query",
     * description="holiday_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="has_this_project",
     * in="query",
     * description="has_this_project",
     * required=true,
     * example="1"
     * ),
     *
     *      *     @OA\Parameter(
     * name="business_id",
     * in="query",
     * description="business_id",
     * required=true,
     * example="1"
     * ),
     *
     *  *      *     @OA\Parameter(
     * name="employment_status_id",
     * in="query",
     * description="employment_status_id",
     * required=true,
     * example="1"
     * ),
     *      *  *      *     @OA\Parameter(
     * name="immigration_status",
     * in="query",
     * description="immigration_status",
     * required=true,
     * example="immigration_status"
     * ),
     *      *  @OA\Parameter(
     * name="pension_scheme_status",
     * in="query",
     * description="pension_scheme_status",
     * required=true,
     * example="pension_scheme_status"
     * ),
     *  @OA\Parameter(
     * name="sponsorship_status",
     * in="query",
     * description="sponsorship_status",
     * required=true,
     * example="sponsorship_status"
     * ),

     * *  @OA\Parameter(
     * name="sponsorship_note",
     * in="query",
     * description="sponsorship_note",
     * required=true,
     * example="sponsorship_note"
     * ),
     * *  @OA\Parameter(
     * name="sponsorship_certificate_number",
     * in="query",
     * description="sponsorship_certificate_number",
     * required=true,
     * example="sponsorship_certificate_number"
     * ),
     * *  @OA\Parameter(
     * name="sponsorship_current_certificate_status",
     * in="query",
     * description="sponsorship_current_certificate_status",
     * required=true,
     * example="sponsorship_current_certificate_status"
     * ),
     * *  @OA\Parameter(
     * name="sponsorship_is_sponsorship_withdrawn",
     * in="query",
     * description="sponsorship_is_sponsorship_withdrawn",
     * required=true,
     * example="0"
     * ),
     *  * *  @OA\Parameter(
     * name="start_joining_date",
     * in="query",
     * description="start_joining_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *  *  * *  @OA\Parameter(
     * name="end_joining_date",
     * in="query",
     * description="end_joining_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *
     *    *    *  *   @OA\Parameter(
     * name="start_pension_pension_enrollment_issue_date",
     * in="query",
     * description="start_pension_pension_enrollment_issue_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *   @OA\Parameter(
     * name="end_pension_pension_enrollment_issue_date",
     * in="query",
     * description="end_pension_pension_enrollment_issue_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *    *  *   @OA\Parameter(
     * name="start_pension_re_enrollment_due_date_date",
     * in="query",
     * description="start_pension_re_enrollment_due_date_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *   @OA\Parameter(
     * name="end_pension_re_enrollment_due_date_date",
     * in="query",
     * description="end_pension_re_enrollment_due_date_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *    *   @OA\Parameter(
     * name="pension_re_enrollment_due_date_in_day",
     * in="query",
     * description="pension_re_enrollment_due_date_in_day",
     * required=true,
     * example="50"
     * ),
     *
     *
     * @OA\Parameter(
     * name="start_sponsorship_date_assigned",
     * in="query",
     * description="start_sponsorship_date_assigned",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *   @OA\Parameter(
     * name="end_sponsorship_date_assigned",
     * in="query",
     * description="end_sponsorship_date_assigned",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *    *  *   @OA\Parameter(
     * name="start_sponsorship_expiry_date",
     * in="query",
     * description="start_sponsorship_expiry_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *   @OA\Parameter(
     * name="end_sponsorship_expiry_date",
     * in="query",
     * description="end_sponsorship_expiry_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *    *   @OA\Parameter(
     * name="sponsorship_expires_in_day",
     * in="query",
     * description="sponsorship_expires_in_day",
     * required=true,
     * example="50"
     * ),
     *
     *
     *      *    *  *   @OA\Parameter(
     * name="start_passport_issue_date",
     * in="query",
     * description="start_passport_issue_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *   @OA\Parameter(
     * name="end_passport_issue_date",
     * in="query",
     * description="end_passport_issue_date",
     * required=true,
     * example="2024-01-21"
     * ),
     * @OA\Parameter(
     * name="start_passport_expiry_date",
     * in="query",
     * description="start_passport_expiry_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *   @OA\Parameter(
     * name="end_passport_expiry_date",
     * in="query",
     * description="end_passport_expiry_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *   *    *   @OA\Parameter(
     * name="passport_expires_in_day",
     * in="query",
     * description="passport_expires_in_day",
     * required=true,
     * example="50"
     * ),
     *     * @OA\Parameter(
     * name="start_visa_issue_date",
     * in="query",
     * description="start_visa_issue_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *   @OA\Parameter(
     * name="end_visa_issue_date",
     * in="query",
     * description="end_visa_issue_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *      *     * @OA\Parameter(
     * name="start_visa_expiry_date",
     * in="query",
     * description="start_visa_expiry_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     *   @OA\Parameter(
     * name="end_visa_expiry_date",
     * in="query",
     * description="end_visa_expiry_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *     @OA\Parameter(
     * name="visa_expires_in_day",
     * in="query",
     * description="visa_expires_in_day",
     * required=true,
     * example="50"
     * ),
     * * @OA\Parameter(
     * name="start_right_to_work_check_date",
     * in="query",
     * description="start_right_to_work_check_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     * @OA\Parameter(
     * name="end_right_to_work_check_date",
     * in="query",
     * description="end_right_to_work_check_date",
     * required=true,
     * example="2024-01-21"
     * ),
     * @OA\Parameter(
     * name="start_right_to_work_expiry_date",
     * in="query",
     * description="start_right_to_work_expiry_date",
     * required=true,
     * example="2024-01-21"
     * ),
     *
     * @OA\Parameter(
     * name="end_right_to_work_expiry_date",
     * in="query",
     * description="end_right_to_work_expiry_date",
     * required=true,
     * example="2024-01-21"
     * ),
     * @OA\Parameter(
     * name="right_to_work_expires_in_day",
     * in="query",
     * description="right_to_work_expires_in_day",
     * required=true,
     * example="50"
     * ),

     *
     *
     *  *      *     @OA\Parameter(
     * name="project_id",
     * in="query",
     * description="project_id",
     * required=true,
     * example="1"
     * ),
     *     * @OA\Parameter(
     * name="department_id",
     * in="query",
     * description="department_id",
     * required=true,
     * example="1"
     * ),
     *
     * *      *   * *  @OA\Parameter(
     * name="doesnt_have_payrun",
     * in="query",
     * description="doesnt_have_payrun",
     * required=true,
     * example="1"
     * ),
     *
     *      *   * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *
     *    * *  @OA\Parameter(
     * name="role",
     * in="query",
     * description="role",
     * required=true,
     * example="admin,manager"
     * ),
     *
     *  @OA\Parameter(
     * name="is_on_holiday",
     * in="query",
     * description="is_on_holiday",
     * required=true,
     * example="1"
     * ),
     *
     *  *  @OA\Parameter(
     * name="upcoming_expiries",
     * in="query",
     * description="upcoming_expiries",
     * required=true,
     * example="passport"
     * ),
     *
     *
     *
     *
     *      summary="This method is to get user",
     *      description="This method is to get user",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getUsers(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $total_departments = Department::where([
                "business_id" => auth()->user()->business_id,
                "is_active" => 1
            ])->count();

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $today = today();

            $users = User::with(
                [
                    "designation" => function ($query) {
                        $query->select(
                            'designations.id',
                            'designations.name',
                        );
                    },
                    "roles",
                    "work_location"
                ]
            )

                ->whereNotIn('id', [$request->user()->id])


                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if (auth()->user()->hasRole("superadmin")) {
                        return  $query->where(function ($query) {
                            return   $query->where('business_id', NULL)
                                ->orWhere(function ($query) {
                                    return $query
                                        ->whereNotNull("business_id")
                                        ->whereHas("roles", function ($query) {
                                            return $query->where("roles.name", "business_owner");
                                        });
                                });
                        });
                    } else {
                        return  $query->where(function ($query) {
                            return   $query->where('created_by', auth()->user()->id);
                        });
                    }
                })

                ->when(!empty(auth()->user()->business_id), function ($query) use ($request, $all_manager_department_ids) {
                    return $query->where(function ($query) use ($all_manager_department_ids) {
                        return  $query->where('business_id', auth()->user()->business_id)
                            ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                                $query->whereIn("departments.id", $all_manager_department_ids);
                            });
                    });
                })
                ->when(!empty($request->role), function ($query) use ($request) {
                    $rolesArray = explode(',', $request->role);
                    return   $query->whereHas("roles", function ($q) use ($rolesArray) {
                        return $q->whereIn("name", $rolesArray);
                    });
                })





                ->when(!empty($request->full_name), function ($query) use ($request) {
                    // Replace spaces with commas and create an array
                    $searchTerms = explode(',', str_replace(' ', ',', $request->full_name));

                    $query->where(function ($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere(function ($subquery) use ($term) {
                                $subquery->where("first_Name", "like", "%" . $term . "%")
                                    ->orWhere("last_Name", "like", "%" . $term . "%")
                                    ->orWhere("middle_Name", "like", "%" . $term . "%");
                            });
                        }
                    });
                })



                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return   $query->where([
                        "user_id" => $request->user_id
                    ]);
                })

                ->when(!empty($request->email), function ($query) use ($request) {
                    return   $query->where([
                        "email" => $request->email
                    ]);
                })


                ->when(!empty($request->designation_id), function ($query) use ($request) {
                    $idsArray = explode(',', $request->designation_id);
                    return $query->whereIn('designation_id', $idsArray);
                })

                ->when(!empty($request->employment_status_id), function ($query) use ($request) {
                    $idsArray = explode(',', $request->employment_status_id);
                    return $query->whereIn('employment_status_id', ($idsArray));
                })

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    $term = $request->search_key;
                    return $query->where(function ($subquery) use ($term) {
                        $subquery->where("first_Name", "like", "%" . $term . "%")
                            ->orWhere("last_Name", "like", "%" . $term . "%")
                            ->orWhere("email", "like", "%" . $term . "%")
                            ->orWhere("phone", "like", "%" . $term . "%");
                    });
                })


                ->when(isset($request->is_in_employee), function ($query) use ($request) {
                    return $query->where('is_in_employee', intval($request->is_in_employee));
                })

                ->when(isset($request->is_on_holiday), function ($query) use ($today, $total_departments, $request) {
                    if (intval($request->is_on_holiday) == 1) {
                        $query
                            ->where("business_id", auth()->user()->business_id)

                            ->where(function ($query) use ($today, $total_departments) {
                                $query->where(function ($query) use ($today, $total_departments) {
                                    $query->where(function ($query) use ($today, $total_departments) {
                                        $query->whereHas('holidays', function ($query) use ($today) {
                                            $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay())
                                                ->where('holidays.end_date', ">=",  $today->copy()->endOfDay());
                                        })
                                            ->orWhere(function ($query) use ($today, $total_departments) {
                                                $query->whereHasRecursiveHolidays($today, $total_departments);
                                            });
                                    })
                                        ->where(function ($query) use ($today) {
                                            $query->orWhereDoesntHave('holidays', function ($query) use ($today) {
                                                $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay())
                                                    ->where('holidays.end_date', ">=",  $today->copy()->endOfDay())
                                                    ->orWhere(function ($query) {
                                                        $query->whereDoesntHave("users")
                                                            ->whereDoesntHave("departments");
                                                    });
                                            });
                                        });
                                })
                                    ->orWhere(
                                        function ($query) use ($today) {
                                            $query->orWhereDoesntHave('holidays', function ($query) use ($today) {
                                                $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay());
                                                $query->where('holidays.end_date', ">=",  $today->copy()->endOfDay());
                                                $query->doesntHave('users');
                                            });
                                        }
                                    );
                            });
                    } else {
                        // Inverted logic for when employees are not on holiday
                        $query->where(function ($query) use ($today, $total_departments) {
                            $query->whereDoesntHave('holidays')
                                ->orWhere(function ($query) use ($today, $total_departments) {
                                    $query->whereDoesntHave('departments')
                                        ->orWhereHas('departments', function ($subQuery) use ($today, $total_departments) {
                                            $subQuery->whereDoesntHave('holidays');
                                        });
                                });
                        });
                    }
                })


                ->when(!empty($request->upcoming_expiries), function ($query) use ($request) {

                    if ($request->upcoming_expiries == "passport") {
                        $query->whereHas("passport_detail", function ($query) {
                            $query->where("employee_passport_detail_histories.passport_expiry_date", ">=", today());
                        });
                    } else if ($request->upcoming_expiries == "visa") {
                        $query->whereHas("visa_detail", function ($query) {
                            $query->where("employee_visa_detail_histories.visa_expiry_date", ">=", today());
                        });
                    } else if ($request->upcoming_expiries == "right_to_work") {
                        $query->whereHas("right_to_work", function ($query) {
                            $query->where("employee_right_to_work_histories.right_to_work_expiry_date", ">=", today());
                        });
                    } else if ($request->upcoming_expiries == "sponsorship") {
                        $query->whereHas("sponsorship_details", function ($query) {
                            $query->where("employee_sponsorship_histories.expiry_date", ">=", today());
                        });
                    } else if ($request->upcoming_expiries == "pension") {
                        $query->whereHas("pension_details", function ($query) {
                            $query->where("employee_pensions.pension_re_enrollment_due_date", ">=", today());
                        });
                    }
                })


                ->when(!empty($request->immigration_status), function ($query) use ($request) {
                    return $query->where('immigration_status', ($request->immigration_status));
                })
                ->when(!empty($request->sponsorship_status), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorship_histories.status", $request->sponsorship_status);
                    });
                })


                ->when(!empty($request->sponsorship_note), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorship_histories.note", $request->sponsorship_note);
                    });
                })
                ->when(!empty($request->sponsorship_certificate_number), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorship_histories.certificate_number", $request->sponsorship_certificate_number);
                    });
                })
                ->when(!empty($request->sponsorship_current_certificate_status), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorship_histories.current_certificate_status", $request->sponsorship_current_certificate_status);
                    });
                })
                ->when(isset($request->sponsorship_is_sponsorship_withdrawn), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorship_histories.is_sponsorship_withdrawn", intval($request->sponsorship_is_sponsorship_withdrawn));
                    });
                })

                ->when(!empty($request->project_id), function ($query) use ($request) {
                    return $query->whereHas("projects", function ($query) use ($request) {
                        $query->where("projects.id", $request->project_id);
                    });
                })
                ->when(!empty($request->department_id), function ($query) use ($request) {
                    return $query->whereHas("departments", function ($query) use ($request) {
                        $query->where("departments.id", $request->department_id);
                    });
                })


                ->when(!empty($request->work_location_id), function ($query) use ($request) {
                    return $query->where('work_location_id', ($request->work_location_id));
                })
                ->when(!empty($request->holiday_id), function ($query) use ($request) {
                    return $query->whereHas("holidays", function ($query) use ($request) {
                        $query->where("holidays.id", $request->holiday_id);
                    });
                })
                ->when(isset($request->is_active), function ($query) use ($request) {
                    return $query->where('is_active', intval($request->is_active));
                })

                ->when(!empty($request->start_joining_date), function ($query) use ($request) {
                    return $query->where('joining_date', ">=", $request->start_joining_date);
                })
                ->when(!empty($request->end_joining_date), function ($query) use ($request) {
                    return $query->where('joining_date', "<=", ($request->end_joining_date .  ' 23:59:59'));
                })
                ->when(!empty($request->start_sponsorship_date_assigned), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorship_histories.date_assigned", ">=", ($request->start_sponsorship_date_assigned));
                    });
                })
                ->when(!empty($request->end_sponsorship_date_assigned), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorship_histories.date_assigned", "<=", ($request->end_sponsorship_date_assigned . ' 23:59:59'));
                    });
                })


                ->when(!empty($request->start_sponsorship_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorship_histories.expiry_date", ">=", $request->start_sponsorship_expiry_date);
                    });
                })
                ->when(!empty($request->end_sponsorship_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorship_histories.expiry_date", "<=", $request->end_sponsorship_expiry_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->sponsorship_expires_in_day), function ($query) use ($request, $today) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request, $today) {
                        $query_day = Carbon::now()->addDays($request->sponsorship_expires_in_day);
                        $query->whereBetween("employee_sponsorship_histories.expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                    });
                })






















                ->when(!empty($request->start_pension_pension_enrollment_issue_date), function ($query) use ($request) {
                    return $query->whereHas("pension_details", function ($query) use ($request) {
                        $query->where("employee_pension_histories.pension_enrollment_issue_date", ">=", ($request->start_pension_pension_enrollment_issue_date));
                    });
                })
                ->when(!empty($request->end_pension_pension_enrollment_issue_date), function ($query) use ($request) {
                    return $query->whereHas("pension_details", function ($query) use ($request) {
                        $query->where("employee_pension_histories.pension_enrollment_issue_date", "<=", ($request->end_pension_pension_enrollment_issue_date . ' 23:59:59'));
                    });
                })


                ->when(!empty($request->start_pension_pension_re_enrollment_due_date), function ($query) use ($request) {
                    return $query->whereHas("pension_details", function ($query) use ($request) {
                        $query->where("employee_pension_histories.pension_re_enrollment_due_date", ">=", $request->start_pension_pension_re_enrollment_due_date);
                    });
                })
                ->when(!empty($request->end_pension_pension_re_enrollment_due_date), function ($query) use ($request) {
                    return $query->whereHas("pension_details", function ($query) use ($request) {
                        $query->where("employee_pension_histories.pension_re_enrollment_due_date", "<=", $request->end_pension_pension_re_enrollment_due_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->pension_pension_re_enrollment_due_date_in_day), function ($query) use ($request, $today) {
                    return $query->whereHas("pension_details", function ($query) use ($request, $today) {
                        $query_day = Carbon::now()->addDays($request->pension_pension_re_enrollment_due_date_in_day);
                        $query->whereBetween("employee_pension_histories.pension_re_enrollment_due_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                    });
                })

                ->when(!empty($request->pension_scheme_status), function ($query) use ($request) {
                    return $query->whereHas("pension_details", function ($query) use ($request) {
                        $query->where("employee_pension_histories.pension_scheme_status", $request->pension_scheme_status);
                    });
                })
















                ->when(!empty($request->start_passport_issue_date), function ($query) use ($request) {
                    return $query->whereHas("passport_details", function ($query) use ($request) {
                        $query->where("employee_passport_detail_histories.passport_issue_date", ">=", $request->start_passport_issue_date);
                    });
                })
                ->when(!empty($request->end_passport_issue_date), function ($query) use ($request) {
                    return $query->whereHas("passport_details", function ($query) use ($request) {
                        $query->where("employee_passport_detail_histories.passport_issue_date", "<=", $request->end_passport_issue_date . ' 23:59:59');
                    });
                })


                ->when(!empty($request->start_passport_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("passport_details", function ($query) use ($request) {
                        $query->where("employee_passport_detail_histories.passport_expiry_date", ">=", $request->start_passport_expiry_date);
                    });
                })
                ->when(!empty($request->end_passport_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("passport_details", function ($query) use ($request) {
                        $query->where("employee_passport_detail_histories.passport_expiry_date", "<=", $request->end_passport_expiry_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->passport_expires_in_day), function ($query) use ($request, $today) {
                    return $query->whereHas("passport_details", function ($query) use ($request, $today) {
                        $query_day = Carbon::now()->addDays($request->passport_expires_in_day);
                        $query->whereBetween("employee_passport_detail_histories.passport_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                    });
                })
                ->when(!empty($request->start_visa_issue_date), function ($query) use ($request) {
                    return $query->whereHas("visa_details", function ($query) use ($request) {
                        $query->where("employee_visa_detail_histories.visa_issue_date", ">=", $request->start_visa_issue_date);
                    });
                })
                ->when(!empty($request->end_visa_issue_date), function ($query) use ($request) {
                    return $query->whereHas("visa_details", function ($query) use ($request) {
                        $query->where("employee_visa_detail_histories.visa_issue_date", "<=", $request->end_visa_issue_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->start_visa_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("visa_details", function ($query) use ($request) {
                        $query->where("employee_visa_detail_histories.visa_expiry_date", ">=", $request->start_visa_expiry_date);
                    });
                })
                ->when(!empty($request->end_visa_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("visa_details", function ($query) use ($request) {
                        $query->where("employee_visa_detail_histories.visa_expiry_date", "<=", $request->end_visa_expiry_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->visa_expires_in_day), function ($query) use ($request, $today) {
                    return $query->whereHas("visa_details", function ($query) use ($request, $today) {
                        $query_day = Carbon::now()->addDays($request->visa_expires_in_day);
                        $query->whereBetween("employee_visa_detail_histories.visa_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                    });
                })








                ->when(!empty($request->start_right_to_work_check_date), function ($query) use ($request) {
                    return $query->whereHas("right_to_works", function ($query) use ($request) {
                        $query->where("employee_right_to_work_histories.right_to_work_check_date", ">=", $request->start_right_to_work_check_date);
                    });
                })
                ->when(!empty($request->end_right_to_work_check_date), function ($query) use ($request) {
                    return $query->whereHas("right_to_works", function ($query) use ($request) {
                        $query->where("employee_right_to_work_histories.right_to_work_check_date", "<=", $request->end_right_to_work_check_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->start_right_to_work_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("right_to_works", function ($query) use ($request) {
                        $query->where("employee_right_to_work_histories.right_to_work_expiry_date", ">=", $request->start_right_to_work_expiry_date);
                    });
                })
                ->when(!empty($request->end_right_to_work_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("right_to_works", function ($query) use ($request) {
                        $query->where("employee_right_to_work_histories.right_to_work_expiry_date", "<=", $request->end_right_to_work_expiry_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->right_to_work_expires_in_day), function ($query) use ($request, $today) {
                    return $query->whereHas("right_to_works", function ($query) use ($request, $today) {
                        $query_day = Carbon::now()->addDays($request->right_to_work_expires_in_day);
                        $query->whereBetween("employee_right_to_work_histories.right_to_work_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                    });
                })



                ->when(isset($request->doesnt_have_payrun), function ($query) use ($request) {
                    if (intval($request->doesnt_have_payrun)) {
                        return $query->whereDoesntHave("payrun_users");
                    } else {
                        return $query;
                    }
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })

                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.id", "DESC");
                })

                ->withCount('all_users as user_count')
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });



            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    $pdf = PDF::loadView('pdf.users', ["users" => $users]);
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new UsersExport($users), ((!empty($request->file_name) ? $request->file_name : 'employee') . '.csv'));
                }
            } else {
                return response()->json($users, 200);
            }
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v2.0/users",
     *      operationId="getUsersV2",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *   * *  @OA\Parameter(
     * name="is_in_employee",
     * in="query",
     * description="is_in_employee",
     * required=true,
     * example="1"
     * ),
     *    *   * *  @OA\Parameter(
     * name="business_id",
     * in="query",
     * description="business_id",
     * required=true,
     * example="1"
     * ),
     *   *   * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *
     *
     *    * *  @OA\Parameter(
     * name="role",
     * in="query",
     * description="role",
     * required=true,
     * example="admin,manager"
     * ),
     *      summary="This method is to get user",
     *      description="This method is to get user",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getUsersV2(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $users = User::with(
                [
                    "designation" => function ($query) {
                        $query->select(
                            'designations.id',
                            'designations.name',
                        );
                    },
                    "roles",
                    "recruitment_processes",
                    "work_location"
                ]
            )

                ->whereNotIn('id', [$request->user()->id])

                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if (auth()->user()->hasRole("superadmin")) {
                        return  $query->where(function ($query) {
                            return   $query->where('business_id', NULL)
                                ->orWhere(function ($query) {
                                    return $query
                                        ->whereNotNull("business_id")
                                        ->whereHas("roles", function ($query) {
                                            return $query->where("roles.name", "business_owner");
                                        });
                                });
                        });
                    } else {
                        return  $query->where(function ($query) {
                            return   $query->where('created_by', auth()->user()->id);
                        });
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request, $all_manager_department_ids) {
                    return $query->where(function ($query) use ($all_manager_department_ids) {
                        return  $query->where('business_id', auth()->user()->business_id)
                            ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                                $query->whereIn("departments.id", $all_manager_department_ids);
                            });;
                    });
                })


                ->when(!empty($request->role), function ($query) use ($request) {
                    $rolesArray = explode(',', $request->role);
                    return   $query->whereHas("roles", function ($q) use ($rolesArray) {
                        return $q->whereIn("name", $rolesArray);
                    });
                })



                ->when(!empty($request->search_key), function ($query) use ($request) {
                    $term = $request->search_key;
                    return $query->where(function ($subquery) use ($term) {
                        $subquery->where("first_Name", "like", "%" . $term . "%")
                            ->orWhere("last_Name", "like", "%" . $term . "%")
                            ->orWhere("email", "like", "%" . $term . "%")
                            ->orWhere("phone", "like", "%" . $term . "%");
                    });
                })

                ->when(isset($request->is_in_employee), function ($query) use ($request) {
                    return $query->where('is_in_employee', intval($request->is_in_employee));
                })
                ->when(isset($request->is_active), function ($query) use ($request) {
                    return $query->where('is_active', intval($request->is_active));
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })

                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.id", "DESC");
                })

                ->withCount('all_users as user_count')
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });

            $data["data"] = $users;
            $data["data_highlights"] = [];

            $data["data_highlights"]["total_active_users"] = $users->filter(function ($user) {
                return $user->is_active == 1;
            })->count();
            $data["data_highlights"]["total_users"] = $users->count();

            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v3.0/users",
     *      operationId="getUsersV3",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *   * *  @OA\Parameter(
     * name="is_in_employee",
     * in="query",
     * description="is_in_employee",
     * required=true,
     * example="1"
     * ),
     *    *   * *  @OA\Parameter(
     * name="business_id",
     * in="query",
     * description="business_id",
     * required=true,
     * example="1"
     * ),
     *   *   * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *
     *
     *    * *  @OA\Parameter(
     * name="role",
     * in="query",
     * description="role",
     * required=true,
     * example="admin,manager"
     * ),
     *      summary="This method is to get user",
     *      description="This method is to get user",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getUsersV3(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();



            $users = User::with(
                [
                    "designation" => function ($query) {
                        $query->select(
                            'designations.id',
                            'designations.name',
                        );
                    },
                    "roles",
                    "recruitment_processes",
                    "work_location"
                ]
            )

                ->whereNotIn('id', [$request->user()->id])

                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if (auth()->user()->hasRole("superadmin")) {
                        return  $query->where(function ($query) {
                            return   $query->where('business_id', NULL)
                                ->orWhere(function ($query) {
                                    return $query
                                        ->whereNotNull("business_id")
                                        ->whereHas("roles", function ($query) {
                                            return $query->where("roles.name", "business_owner");
                                        });
                                });
                        });
                    } else {
                        return  $query->where(function ($query) {
                            return   $query->where('created_by', auth()->user()->id);
                        });
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request, $all_manager_department_ids) {
                    return $query->where(function ($query) use ($all_manager_department_ids) {
                        return  $query->where('business_id', auth()->user()->business_id)
                            ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                                $query->whereIn("departments.id", $all_manager_department_ids);
                            });;
                    });
                })


                ->when(!empty($request->role), function ($query) use ($request) {
                    $rolesArray = explode(',', $request->role);
                    return   $query->whereHas("roles", function ($q) use ($rolesArray) {
                        return $q->whereIn("name", $rolesArray);
                    });
                })



                ->when(!empty($request->search_key), function ($query) use ($request) {
                    $term = $request->search_key;
                    return $query->where(function ($subquery) use ($term) {
                        $subquery->where("first_Name", "like", "%" . $term . "%")
                            ->orWhere("last_Name", "like", "%" . $term . "%")
                            ->orWhere("email", "like", "%" . $term . "%")
                            ->orWhere("phone", "like", "%" . $term . "%");
                    });
                })

                ->when(isset($request->is_in_employee), function ($query) use ($request) {
                    return $query->where('is_in_employee', intval($request->is_in_employee));
                })
                ->when(isset($request->is_active), function ($query) use ($request) {
                    return $query->where('is_active', intval($request->is_active));
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })

                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.id", "DESC");
                })

                ->withCount('all_users as user_count')
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });

            $data["data"] = $users;
            $data["data_highlights"] = [];

            $data["data_highlights"]["total_active_users"] = $users->filter(function ($user) {
                return $user->is_active == 1;
            })->count();
            $data["data_highlights"]["total_users"] = $users->count();

            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v4.0/users",
     *      operationId="getUsersV4",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *   * *  @OA\Parameter(
     * name="is_in_employee",
     * in="query",
     * description="is_in_employee",
     * required=true,
     * example="1"
     * ),
     *
     *      *     @OA\Parameter(
     * name="business_id",
     * in="query",
     * description="business_id",
     * required=true,
     * example="1"
     * ),
     *
     *
     *      *   * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *
     *    * *  @OA\Parameter(
     * name="role",
     * in="query",
     * description="role",
     * required=true,
     * example="admin,manager"
     * ),
     *      summary="This method is to get user",
     *      description="This method is to get user",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getUsersV4(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $users = User::whereNotIn('id', [$request->user()->id])
                ->when(!empty($request->role), function ($query) use ($request) {
                    $rolesArray = explode(',', $request->role);
                    return   $query->whereHas("roles", function ($q) use ($rolesArray) {
                        return $q->whereIn("name", $rolesArray);
                    });
                })
                ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                    return $query->where(function ($query) {
                        return  $query->where('created_by', auth()->user()->id)

                            ->orWhere('business_id', auth()->user()->business_id);
                    });
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    $term = $request->search_key;
                    return $query->where(function ($subquery) use ($term) {
                        $subquery->where("first_Name", "like", "%" . $term . "%")
                            ->orWhere("last_Name", "like", "%" . $term . "%")
                            ->orWhere("email", "like", "%" . $term . "%")
                            ->orWhere("phone", "like", "%" . $term . "%");
                    });
                })
                ->when(empty($request->user()->business_id), function ($query) use ($request) {
                    if (empty($request->business_id)) {
                        return $query->where('business_id', NULL);
                    }
                    return $query->where('business_id', intval($request->business_id));
                })
                ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                    return $query->where('business_id', $request->user()->business_id);
                })
                ->when(isset($request->is_in_employee), function ($query) use ($request) {
                    return $query->where('is_in_employee', intval($request->is_in_employee));
                })
                ->when(isset($request->is_active), function ($query) use ($request) {
                    return $query->where('is_active', intval($request->is_active));
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.id", "DESC");
                })

                ->select(
                    'user_name',
                    'first_Name',
                    'last_Name',
                    'middle_Name',
                    "NI_number",
                    'gender',
                    'phone',
                    'image',
                    'address_line_1',
                    'address_line_2',
                    'country',
                    'city',
                    'postcode',
                    "lat",
                    "long"
                )
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });

            return response()->json($users, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/{id}",
     *
     *      operationId="getUserById",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *   *   *              @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="response_type: in pdf, json",
     *         required=true,
     *  example="json"
     *      ),
     *     @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),
     *
     *
     *  * @OA\Parameter(
     *     name="employee_details",
     *     in="query",
     *     description="Employee Details",
     *     required=true,
     *     example="employee_details"
     * ),
     * @OA\Parameter(
     *     name="leave_allowances",
     *     in="query",
     *     description="Leave Allowances",
     *     required=true,
     *     example="leave_allowances"
     * ),
     * @OA\Parameter(
     *     name="attendances",
     *     in="query",
     *     description="Attendances",
     *     required=true,
     *     example="attendances"
     * ),
     * @OA\Parameter(
     *     name="leaves",
     *     in="query",
     *     description="Leaves",
     *     required=true,
     *     example="leaves"
     * ),
     * @OA\Parameter(
     *     name="documents",
     *     in="query",
     *     description="Documents",
     *     required=true,
     *     example="documents"
     * ),
     * @OA\Parameter(
     *     name="assets",
     *     in="query",
     *     description="Assets",
     *     required=true,
     *     example="assets"
     * ),
     * @OA\Parameter(
     *     name="educational_history",
     *     in="query",
     *     description="Educational History",
     *     required=true,
     *     example="educational_history"
     * ),
     * @OA\Parameter(
     *     name="job_history",
     *     in="query",
     *     description="Job History",
     *     required=true,
     *     example="job_history"
     * ),
     * @OA\Parameter(
     *     name="current_cos_details",
     *     in="query",
     *     description="Current COS Details",
     *     required=true,
     *     example="current_cos_details"
     * ),
     *
     *
     *    * @OA\Parameter(
     *     name="current_pension_details",
     *     in="query",
     *     description="Current COS Details",
     *     required=true,
     *     example="current_pension_details"
     * ),
     *
     *
     *
     * @OA\Parameter(
     *     name="current_passport_details",
     *     in="query",
     *     description="Current Passport Details",
     *     required=true,
     *     example="current_passport_details"
     * ),
     * @OA\Parameter(
     *     name="current_visa_details",
     *     in="query",
     *     description="Current Visa Details",
     *     required=true,
     *     example="current_visa_details"
     * ),
     *   * @OA\Parameter(
     *     name="current_right_to_works",
     *     in="query",
     *     description="Current right to works",
     *     required=true,
     *     example="current_right_to_works"
     * ),
     * @OA\Parameter(
     *     name="address_details",
     *     in="query",
     *     description="Address Details",
     *     required=true,
     *     example="address_details"
     * ),
     * @OA\Parameter(
     *     name="contact_details",
     *     in="query",
     *     description="Contact Details",
     *     required=true,
     *     example="contact_details"
     * ),
     * @OA\Parameter(
     *     name="notes",
     *     in="query",
     *     description="Notes",
     *     required=true,
     *     example="notes"
     * ),
     * @OA\Parameter(
     *     name="bank_details",
     *     in="query",
     *     description="Bank Details",
     *     required=true,
     *     example="bank_details"
     * ),
     * @OA\Parameter(
     *     name="social_links",
     *     in="query",
     *     description="Social Links",
     *     required=true,
     *     example="social_links"
     * ),
     *  * @OA\Parameter(
     *     name="employee_details_name",
     *     in="query",
     *     description="Employee Name",
     *     required=true,
     *     example="John Doe"
     * ),
     * @OA\Parameter(
     *     name="employee_details_user_id",
     *     in="query",
     *     description="Employee User ID",
     *     required=true,
     *     example="123456"
     * ),
     * @OA\Parameter(
     *     name="employee_details_email",
     *     in="query",
     *     description="Employee Email",
     *     required=true,
     *     example="john.doe@example.com"
     * ),
     * @OA\Parameter(
     *     name="employee_details_phone",
     *     in="query",
     *     description="Employee Phone",
     *     required=true,
     *     example="123-456-7890"
     * ),
     * @OA\Parameter(
     *     name="employee_details_gender",
     *     in="query",
     *     description="Employee Gender",
     *     required=true,
     *     example="male"
     * ),
     * @OA\Parameter(
     *     name="leave_allowance_name",
     *     in="query",
     *     description="Leave Allowance Name",
     *     required=true,
     *     example="Annual Leave"
     * ),
     * @OA\Parameter(
     *     name="leave_allowance_type",
     *     in="query",
     *     description="Leave Allowance Type",
     *     required=true,
     *     example="Paid"
     * ),
     * @OA\Parameter(
     *     name="leave_allowance_allowance",
     *     in="query",
     *     description="Leave Allowance Amount",
     *     required=true,
     *     example="20"
     * ),
     * @OA\Parameter(
     *     name="leave_allowance_earned",
     *     in="query",
     *     description="Leave Allowance Earned",
     *     required=true,
     *     example="10"
     * ),
     * @OA\Parameter(
     *     name="leave_allowance_availability",
     *     in="query",
     *     description="Leave Allowance Availability",
     *     required=true,
     *     example="Yes"
     * ),
     * @OA\Parameter(
     *     name="attendance_date",
     *     in="query",
     *     description="Attendance Date",
     *     required=true,
     *     example="2024-02-13"
     * ),
     * @OA\Parameter(
     *     name="attendance_start_time",
     *     in="query",
     *     description="Attendance Start Time",
     *     required=true,
     *     example="08:00:00"
     * ),
     * @OA\Parameter(
     *     name="attendance_end_time",
     *     in="query",
     *     description="Attendance End Time",
     *     required=true,
     *     example="17:00:00"
     * ),
     * @OA\Parameter(
     *     name="attendance_break",
     *     in="query",
     *     description="Attendance Break Time",
     *     required=true,
     *     example="01:00:00"
     * ),
     * @OA\Parameter(
     *     name="attendance_schedule",
     *     in="query",
     *     description="Attendance Schedule",
     *     required=true,
     *     example="Regular"
     * ),
     * @OA\Parameter(
     *     name="attendance_overtime",
     *     in="query",
     *     description="Attendance Overtime",
     *     required=true,
     *     example="02:00:00"
     * ),
     * @OA\Parameter(
     *     name="leave_date_time",
     *     in="query",
     *     description="Leave Date and Time",
     *     required=true,
     *     example="2024-02-14 08:00:00"
     * ),
     * @OA\Parameter(
     *     name="leave_type",
     *     in="query",
     *     description="Leave Type",
     *     required=true,
     *     example="Sick Leave"
     * ),
     * @OA\Parameter(
     *     name="leave_duration",
     *     in="query",
     *     description="Leave Duration",
     *     required=true,
     *     example="8"
     * ),
     * @OA\Parameter(
     *     name="total_leave_hours",
     *     in="query",
     *     description="Total Leave Hours",
     *     required=true,
     *     example="8"
     * ),
     * @OA\Parameter(
     *     name="document_title",
     *     in="query",
     *     description="Document Title",
     *     required=true,
     *     example="Annual Report"
     * ),
     * @OA\Parameter(
     *     name="document_added_by",
     *     in="query",
     *     description="Document Added By",
     *     required=true,
     *     example="Jane Smith"
     * ),
     * @OA\Parameter(
     *     name="asset_name",
     *     in="query",
     *     description="Asset Name",
     *     required=true,
     *     example="Laptop"
     * ),
     * @OA\Parameter(
     *     name="asset_code",
     *     in="query",
     *     description="Asset Code",
     *     required=true,
     *     example="LT12345"
     * ),
     * @OA\Parameter(
     *     name="asset_serial_number",
     *     in="query",
     *     description="Asset Serial Number",
     *     required=true,
     *     example="SN6789"
     * ),
     * @OA\Parameter(
     *     name="asset_is_working",
     *     in="query",
     *     description="Is Asset Working",
     *     required=true,
     *     example="true"
     * ),
     * @OA\Parameter(
     *     name="asset_type",
     *     in="query",
     *     description="Asset Type",
     *     required=true,
     *     example="Electronic"
     * ),
     * @OA\Parameter(
     *     name="asset_date",
     *     in="query",
     *     description="Asset Date",
     *     required=true,
     *     example="2024-02-13"
     * ),
     * @OA\Parameter(
     *     name="asset_note",
     *     in="query",
     *     description="Asset Note",
     *     required=true,
     *     example="This is a laptop for development purposes."
     * ),
     * @OA\Parameter(
     *     name="educational_history_degree",
     *     in="query",
     *     description="Educational History Degree",
     *     required=true,
     *     example="Bachelor of Science"
     * ),
     * @OA\Parameter(
     *     name="educational_history_major",
     *     in="query",
     *     description="Educational History Major",
     *     required=true,
     *     example="Computer Science"
     * ),
     * @OA\Parameter(
     *     name="educational_history_start_date",
     *     in="query",
     *     description="Educational History Start Date",
     *     required=true,
     *     example="2018-09-01"
     * ),
     * @OA\Parameter(
     *     name="educational_history_achievements",
     *     in="query",
     *     description="Educational History Achievements",
     *     required=true,
     *     example="Graduated with honors"
     * ),
     * @OA\Parameter(
     *     name="job_history_job_title",
     *     in="query",
     *     description="Job History Job Title",
     *     required=true,
     *     example="Software Engineer"
     * ),
     * @OA\Parameter(
     *     name="job_history_company",
     *     in="query",
     *     description="Job History Company",
     *     required=true,
     *     example="Tech Solutions Inc."
     * ),
     * @OA\Parameter(
     *     name="job_history_start_on",
     *     in="query",
     *     description="Job History Start Date",
     *     required=true,
     *     example="2020-03-15"
     * ),
     * @OA\Parameter(
     *     name="job_history_end_at",
     *     in="query",
     *     description="Job History End Date",
     *     required=true,
     *     example="2022-05-30"
     * ),
     * @OA\Parameter(
     *     name="job_history_supervisor",
     *     in="query",
     *     description="Job History Supervisor",
     *     required=true,
     *     example="John Smith"
     * ),
     * @OA\Parameter(
     *     name="job_history_country",
     *     in="query",
     *     description="Job History Country",
     *     required=true,
     *     example="United States"
     * ),
     *
     *  * @OA\Parameter(
     *     name="current_pension_details_pension_scheme_status",
     *     in="query",
     *     description="current_pension_details_pension_scheme_status",
     *     required=true,
     *     example="2023-05-15"
     * ),
     *  * @OA\Parameter(
     *     name="current_pension_details_pension_enrollment_issue_date",
     *     in="query",
     *     description="current_pension_details_pension_enrollment_issue_date",
     *     required=true,
     *     example="2023-05-15"
     * ),
     *  * @OA\Parameter(
     *     name="current_pension_details_pension_scheme_opt_out_date",
     *     in="query",
     *     description="current_pension_details_pension_scheme_opt_out_date",
     *     required=true,
     *     example="2023-05-15"
     * ),
     *  * @OA\Parameter(
     *     name="current_pension_details_pension_re_enrollment_due_date",
     *     in="query",
     *     description="current_pension_details_pension_re_enrollment_due_date",
     *     required=true,
     *     example="2023-05-15"
     * ),

     *

     *
     * @OA\Parameter(
     *     name="current_cos_details_date_assigned",
     *     in="query",
     *     description="Date COS Assigned",
     *     required=true,
     *     example="2023-05-15"
     * ),
     * @OA\Parameter(
     *     name="current_cos_details_expiry_date",
     *     in="query",
     *     description="COS Expiry Date",
     *     required=true,
     *     example="2025-05-14"
     * ),
     * @OA\Parameter(
     *     name="current_cos_details_certificate_number",
     *     in="query",
     *     description="COS Certificate Number",
     *     required=true,
     *     example="COS12345"
     * ),
     * @OA\Parameter(
     *     name="current_cos_details_current_certificate_status",
     *     in="query",
     *     description="Current COS Certificate Status",
     *     required=true,
     *     example="Active"
     * ),
     * @OA\Parameter(
     *     name="current_cos_details_note",
     *     in="query",
     *     description="COS Note",
     *     required=true,
     *     example="Employee is eligible for work under the current COS."
     * ),
     * @OA\Parameter(
     *     name="current_passport_details_issue_date",
     *     in="query",
     *     description="Passport Issue Date",
     *     required=true,
     *     example="2022-01-01"
     * ),
     * @OA\Parameter(
     *     name="current_passport_details_expiry_date",
     *     in="query",
     *     description="Passport Expiry Date",
     *     required=true,
     *     example="2032-01-01"
     * ),
     * @OA\Parameter(
     *     name="current_passport_details_passport_number",
     *     in="query",
     *     description="Passport Number",
     *     required=true,
     *     example="P123456"
     * ),
     * @OA\Parameter(
     *     name="current_passport_details_place_of_issue",
     *     in="query",
     *     description="Passport Place of Issue",
     *     required=true,
     *     example="United Kingdom"
     * ),
     * @OA\Parameter(
     *     name="current_visa_details_issue_date",
     *     in="query",
     *     description="Visa Issue Date",
     *     required=true,
     *     example="2023-01-01"
     * ),
     * @OA\Parameter(
     *     name="current_visa_details_expiry_date",
     *     in="query",
     *     description="Visa Expiry Date",
     *     required=true,
     *     example="2025-01-01"
     * ),
     * @OA\Parameter(
     *     name="current_visa_details_brp_number",
     *     in="query",
     *     description="BRP Number",
     *     required=true,
     *     example="BRP1234567890"
     * ),
     * @OA\Parameter(
     *     name="current_visa_details_place_of_issue",
     *     in="query",
     *     description="Visa Place of Issue",
     *     required=true,
     *     example="United Kingdom"
     * ),
     *
     *  * @OA\Parameter(
     *     name="current_right_to_works_right_to_work_code",
     *     in="query",
     *     description="Right to Work Code",
     *     required=true,
     *     example="123456"
     * ),
     * @OA\Parameter(
     *     name="current_right_to_works_right_to_work_check_date",
     *     in="query",
     *     description="Right to Work Check Date",
     *     required=true,
     *     example="2023-01-01"
     * ),
     * @OA\Parameter(
     *     name="current_right_to_works_right_to_work_expiry_date",
     *     in="query",
     *     description="Right to Work Expiry Date",
     *     required=true,
     *     example="2025-01-01"
     * ),

     * @OA\Parameter(
     *     name="address_details_address",
     *     in="query",
     *     description="Address",
     *     required=true,
     *     example="123 Main Street"
     * ),
     * @OA\Parameter(
     *     name="address_details_city",
     *     in="query",
     *     description="City",
     *     required=true,
     *     example="London"
     * ),
     * @OA\Parameter(
     *     name="address_details_country",
     *     in="query",
     *     description="Country",
     *     required=true,
     *     example="United Kingdom"
     * ),
     * @OA\Parameter(
     *     name="address_details_postcode",
     *     in="query",
     *     description="Postcode",
     *     required=true,
     *     example="AB12 3CD"
     * ),
     * @OA\Parameter(
     *     name="contact_details_first_name",
     *     in="query",
     *     description="First Name",
     *     required=true,
     *     example="John"
     * ),
     * @OA\Parameter(
     *     name="contact_details_last_name",
     *     in="query",
     *     description="Last Name",
     *     required=true,
     *     example="Doe"
     * ),
     * @OA\Parameter(
     *     name="contact_details_relationship",
     *     in="query",
     *     description="Relationship",
     *     required=true,
     *     example="Spouse"
     * ),
     * @OA\Parameter(
     *     name="contact_details_address",
     *     in="query",
     *     description="Address",
     *     required=true,
     *     example="456 Elm Street"
     * ),
     * @OA\Parameter(
     *     name="contact_details_postcode",
     *     in="query",
     *     description="Postcode",
     *     required=true,
     *     example="XY12 3Z"
     * ),
     * @OA\Parameter(
     *     name="contact_details_day_time_tel_number",
     *     in="query",
     *     description="Daytime Telephone Number",
     *     required=true,
     *     example="123-456-7890"
     * ),
     * @OA\Parameter(
     *     name="contact_details_evening_time_tel_number",
     *     in="query",
     *     description="Evening Telephone Number",
     *     required=true,
     *     example="789-456-1230"
     * ),
     * @OA\Parameter(
     *     name="contact_details_mobile_tel_number",
     *     in="query",
     *     description="Mobile Telephone Number",
     *     required=true,
     *     example="987-654-3210"
     * ),
     * @OA\Parameter(
     *     name="notes_title",
     *     in="query",
     *     description="Notes Title",
     *     required=true,
     *     example="Meeting Notes"
     * ),
     * @OA\Parameter(
     *     name="notes_description",
     *     in="query",
     *     description="Notes Description",
     *     required=true,
     *     example="Discussed project progress."
     * ),
     * @OA\Parameter(
     *     name="bank_details_name",
     *     in="query",
     *     description="Bank Name",
     *     required=true,
     *     example="ABC Bank"
     * ),
     * @OA\Parameter(
     *     name="bank_details_sort_code",
     *     in="query",
     *     description="Bank Sort Code",
     *     required=true,
     *     example="12-34-56"
     * ),
     * @OA\Parameter(
     *     name="bank_details_account_name",
     *     in="query",
     *     description="Account Name",
     *     required=true,
     *     example="John Doe"
     * ),
     * @OA\Parameter(
     *     name="bank_details_account_number",
     *     in="query",
     *     description="Account Number",
     *     required=true,
     *     example="12345678"
     * ),
     * @OA\Parameter(
     *     name="social_links_website",
     *     in="query",
     *     description="Website",
     *     required=true,
     *     example="example.com"
     * ),
     * @OA\Parameter(
     *     name="social_links_url",
     *     in="query",
     *     description="Social Media URL",
     *     required=true,
     *     example="https://twitter.com/example"
     * ),

     *
     *

     *      summary="This method is to get user by id",
     *      description="This method is to get user by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getUserById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $user = User::with(
                [
                    "roles",
                    "departments",
                    "designation",
                    "employment_status",
                    "business",
                    "work_location",
                    "pension_detail"

                ]
            )
                ->where([
                    "id" => $id
                ])
                ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                    return $query->where(function ($query) {
                        return  $query->where('created_by', auth()->user()->id)
                            ->orWhere('id', auth()->user()->id)
                            ->orWhere('business_id', auth()->user()->business_id);
                    });
                })
                ->first();
            if (!$user) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }
            // ->whereHas('roles', function ($query) {
            //     // return $query->where('name','!=', 'customer');
            // });
            $user->work_shift = $user->work_shifts()->first();

            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    $pdf = PDF::loadView('pdf.user', ["user" => $user, "request" => $request]);
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new UserExport($user), ((!empty($request->file_name) ? $request->file_name : 'employee') . '.csv'));
                }
            } else {
                return response()->json($user, 200);
            }

            return response()->json($user, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v2.0/users/{id}",
     *      operationId="getUserByIdV2",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),

     *      summary="This method is to get user by id",
     *      description="This method is to get user by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getUserByIdV2($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $user = User::with(
                [
                    "designation" => function ($query) {
                        $query->select(
                            'designations.id',
                            'designations.name',
                        );
                    },
                    "roles",
                    "departments",
                    "employment_status",
                    "sponsorship_details",
                    "passport_details",
                    "visa_details",
                    "right_to_works",
                    "work_shifts",
                    "recruitment_processes",
                    "work_location"
                ]

            )

                ->where([
                    "id" => $id
                ])
                ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                    return $query->where(function ($query) {
                        return  $query->where('created_by', auth()->user()->id)
                            ->orWhere('id', auth()->user()->id)
                            ->orWhere('business_id', auth()->user()->business_id);
                    });
                })
                ->first();
            if (!$user) {

                return response()->json([
                    "message" => "no user found"
                ], 404);
            }
            // ->whereHas('roles', function ($query) {
            //     // return $query->where('name','!=', 'customer');
            // });
            $user->work_shift = $user->work_shifts()->first();

            $user->department_ids = $user->departments->pluck("id");






            return response()->json($user, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/get-leave-details/{id}",
     *      operationId="getLeaveDetailsByUserId",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),

     *      summary="This method is to get user by id",
     *      description="This method is to get user by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getLeaveDetailsByUserId($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();

            // get appropriate use if auth user have access
            $user = $this->getUserByIdUtil($id, $all_manager_department_ids);



            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }



            $setting_leave = SettingLeave::where('setting_leaves.business_id', auth()->user()->business_id)
                ->where('setting_leaves.is_default', 0)
                ->first();
            if (!$setting_leave) {
                return response()->json(
                    ["message" => "No leave setting found."]
                );
            }
            if (!$setting_leave->start_month) {
                $setting_leave->start_month = 1;
            }

            $paid_leave_available = in_array($user->employment_status_id, $setting_leave->paid_leave_employment_statuses()->pluck("employment_statuses.id")->toArray());



            $leave_types =   SettingLeaveType::where(function ($query) use ($paid_leave_available, $created_by) {
                $query->where('setting_leave_types.business_id', auth()->user()->business_id)
                    ->where('setting_leave_types.is_default', 0)
                    ->where('setting_leave_types.is_active', 1)
                    ->when($paid_leave_available == 0, function ($query) {
                        $query->where('setting_leave_types.type', "unpaid");
                    })
                    ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                        $q->whereIn("disabled_setting_leave_types.created_by", [$created_by]);
                    })
                    ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                        $q->whereIn("disabled_setting_leave_types.business_id", [auth()->user()->business_id]);
                    });
            })
                ->get();

            $startOfMonth = Carbon::create(null, $setting_leave->start_month, 1, 0, 0, 0);
            foreach ($leave_types as $key => $leave_type) {
                $total_recorded_hours = LeaveRecord::whereHas('leave', function ($query) use ($user, $leave_type) {
                    $query->where([
                        "user_id" => $user->id,
                        "leave_type_id" => $leave_type->id

                    ]);
                })

                    ->where("leave_records.date", ">=", $startOfMonth)
                    ->get()
                    ->sum(function ($record) {
                        return Carbon::parse($record->end_time)->diffInHours(Carbon::parse($record->start_time));
                    });
                $leave_types[$key]->already_taken_hours = $total_recorded_hours;
            }

            return response()->json($leave_types, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

       /**
     *
     * @OA\Get(
     *      path="/v1.0/users/get-disable-days-for-attendances/{id}",
     *      operationId="getDisableDaysForAttendanceByUserId",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),


     *      summary="This method is to get user by id",
     *      description="This method is to get user by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

     public function getDisableDaysForAttendanceByUserId($id, Request $request)
     {


         foreach (File::glob(storage_path('logs') . '/*.log') as $file) {
             File::delete($file);
         }
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             if (!$request->user()->hasPermissionTo('user_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }

             $all_manager_department_ids = $this->get_all_departments_of_manager();


             $user = User::with("roles")
                 ->where([
                     "id" => $id
                 ])
                 ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                     $query->whereIn("departments.id", $all_manager_department_ids);
                 })
                 ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                     return $query->where(function ($query) {
                         return  $query->where('created_by', auth()->user()->id)
                             ->orWhere('id', auth()->user()->id)
                             ->orWhere('business_id', auth()->user()->business_id);
                     });
                 })
                 ->first();

             if (!$user) {
                 return response()->json([
                     "message" => "no user found"
                 ], 404);
             }




             $start_date = !empty($request->start_date) ? $request->start_date : Carbon::now()->startOfYear()->format('Y-m-d');
             $end_date = !empty($request->end_date) ? $request->end_date : Carbon::now()->endOfYear()->format('Y-m-d');







             $already_taken_attendance_dates = $this->attendanceComponent->get_already_taken_attendance_dates($user->id, $start_date, $end_date);


             $already_taken_full_day_leave_dates = $this->leaveComponent->get_already_taken_leave_dates($start_date, $end_date, $user->id,TRUE);


             $disable_days_collection = collect($already_taken_attendance_dates);


            $disable_days_collection = $disable_days_collection->merge($already_taken_full_day_leave_dates);



             $unique_disable_days_collection = $disable_days_collection->unique();
             $disable_days_array = $unique_disable_days_collection->values()->all();




             $already_taken_hourly_leave_dates = $this->leaveComponent->get_already_taken_half_day_leaves($start_date, $end_date, $user->id);


             $result_array = [
                "disable_days" => $disable_days_array,
                "enable_days_with_condition" => $already_taken_hourly_leave_dates,
             ];

             return response()->json($result_array, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }





    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/get-attendances/{id}",
     *      operationId="getAttendancesByUserId",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      *     *              @OA\Parameter(
     *         name="is_including_leaves",
     *         in="path",
     *         description="is_including_leaves",
     *         required=true,
     *  example="1"
     *      ),
     *    @OA\Parameter(
     *         name="is_full_day_leave",
     *         in="path",
     *         description="is_full_day_leave",
     *         required=true,
     *  example="1"
     *      ),


     *      summary="This method is to get user by id",
     *      description="This method is to get user by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getAttendancesByUserId($id, Request $request)
    {


        foreach (File::glob(storage_path('logs') . '/*.log') as $file) {
            File::delete($file);
        }
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $user = User::with("roles")
                ->where([
                    "id" => $id
                ])
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                    return $query->where(function ($query) {
                        return  $query->where('created_by', auth()->user()->id)
                            ->orWhere('id', auth()->user()->id)
                            ->orWhere('business_id', auth()->user()->business_id);
                    });
                })
                ->first();

            if (!$user) {
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }




            $start_date = !empty($request->start_date) ? $request->start_date : Carbon::now()->startOfYear()->format('Y-m-d');
            $end_date = !empty($request->end_date) ? $request->end_date : Carbon::now()->endOfYear()->format('Y-m-d');







            $already_taken_attendance_dates = $this->attendanceComponent->get_already_taken_attendance_dates($user->id, $start_date, $end_date);







            $already_taken_leave_dates = $this->leaveComponent->get_already_taken_leave_dates($start_date, $end_date, $user->id,(isset($is_full_day_leave)?$is_full_day_leave:NULL));


            $result_collection = collect($already_taken_attendance_dates);

            if (isset($request->is_including_leaves)) {
                if (intval($request->is_including_leaves) == 1) {
                    $result_collection = $result_collection->merge($already_taken_leave_dates);
                }
            }

            $unique_result_collection = $result_collection->unique();
            $result_array = $unique_result_collection->values()->all();




            return response()->json($result_array, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/get-leaves/{id}",
     *      operationId="getLeavesByUserId",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),



     *      summary="This method is to get user by id",
     *      description="This method is to get user by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getLeavesByUserId($id, Request $request)
    {


        foreach (File::glob(storage_path('logs') . '/*.log') as $file) {
            File::delete($file);
        }
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $user = User::with("roles")
                ->where([
                    "id" => $id
                ])
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                    return $query->where(function ($query) {
                        return  $query->where('created_by', auth()->user()->id)
                            ->orWhere('id', auth()->user()->id)
                            ->orWhere('business_id', auth()->user()->business_id);
                    });
                })
                ->first();

            if (!$user) {
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }


            $start_date = !empty($request->start_date) ? $request->start_date : Carbon::now()->startOfYear()->format('Y-m-d');
            $end_date = !empty($request->end_date) ? $request->end_date : Carbon::now()->endOfYear()->format('Y-m-d');


            $already_taken_leave_dates = $this->leaveComponent->get_already_taken_leave_dates($start_date, $end_date, $user->id);


            $result_collection = $already_taken_leave_dates->unique();

            $result_array = $result_collection->values()->all();


            return response()->json($result_array, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/get-holiday-details/{id}",
     *      operationId="getholidayDetailsByUserId",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *     *              @OA\Parameter(
     *         name="is_including_attendance",
     *         in="path",
     *         description="is_including_attendance",
     *         required=true,
     *  example="1"
     *      ),

     *      summary="This method is to get user by id",
     *      description="This method is to get user by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getholidayDetailsByUserId($id, Request $request)
    {


        foreach (File::glob(storage_path('logs') . '/*.log') as $file) {
            File::delete($file);
        }
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            // Check if the user has permission to view users
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // Get all department IDs managed by the current user
            $all_manager_department_ids = $this->get_all_departments_of_manager();

            // Retrieve the user based on the provided ID, ensuring it belongs to one of the managed departments
            $user = User::with("roles")
                ->where([
                    "id" => $id
                ])
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!auth()->user()->hasRole('superadmin'), function ($query) use ($request) {
                    return $query->where(function ($query) {
                        return  $query->where('created_by', auth()->user()->id)
                            ->orWhere('id', auth()->user()->id)
                            ->orWhere('business_id', auth()->user()->business_id);
                    });
                })
                ->first();

            // If no user found, return 404 error
            if (!$user) {
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }

            // Get all parent department IDs of the user
            $all_parent_department_ids = $this->all_parent_departments_of_user($id);

            // Set start and end date for the holiday period
            $start_date = !empty($request->start_date) ? $request->start_date : Carbon::now()->startOfYear()->format('Y-m-d');
            $end_date = !empty($request->end_date) ? $request->end_date : Carbon::now()->endOfYear()->format('Y-m-d');




            // Process holiday dates
            $holiday_dates =  $this->holidayComponent->get_holiday_dates($start_date, $end_date, $user->id, $all_parent_department_ids);


            // Retrieve work shift histories for the user within the specified period
            $work_shift_histories = $this->workShiftHistoryComponent->get_work_shift_histories($start_date, $end_date, $user->id);

            // Initialize an empty collection to store weekend dates

            $weekend_dates = $this->holidayComponent->get_weekend_dates($start_date, $end_date, $user->id, $work_shift_histories);







            // Process already taken leave dates
            $already_taken_leave_dates = $this->leaveComponent->get_already_taken_leave_dates($start_date, $end_date, $user->id);




            // Process already taken attendance dates
            $already_taken_attendance_dates = $this->attendanceComponent->get_already_taken_attendance_dates($user->id, $start_date, $end_date);


            $result_collection = collect($holiday_dates)->merge($weekend_dates)->merge($already_taken_leave_dates);

            if (isset($request->is_including_attendance)) {
                if (intval($request->is_including_attendance) == 1) {
                    $result_collection = $result_collection->merge($already_taken_attendance_dates);
                }
            }


            $unique_result_collection = $result_collection->unique();

            $result_array = $unique_result_collection->values()->all();



            return response()->json($result_array, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }






    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/get-schedule-information/by-user",
     *      operationId="getScheduleInformation",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="start_date",
     *         required=true,
     *         example="start_date"
     *      ),
     *
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="end_date",
     *         required=true,
     *         example="end_date"
     *      ),
     *    *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="user_id",
     *         required=true,
     *         example="1"
     *      ),
     *

     *      summary="This method is to get user by id",
     *      description="This method is to get user by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getScheduleInformation(Request $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $start_date = !empty($request->start_date) ? $request->start_date : Carbon::now()->startOfYear()->format('Y-m-d');
            $end_date = !empty($request->end_date) ? $request->end_date : Carbon::now()->endOfYear()->format('Y-m-d');
            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $employees = User::with(
                ["departments"]
            )
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })

                ->where(["users.business_id" => auth()->user()->business_id])

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                    });
                })

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    $idsArray = explode(',', $request->user_id);
                    return $query->whereIn('users.id', $idsArray);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    $query->whereNotIn("users.id", [auth()->user()->id]);
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.id", "DESC");
                })

                ->select(
                    "users.id",
                    "users.first_Name",
                    "users.middle_Name",
                    "users.last_Name",
                    "users.image",
                )
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });

            $employees->each(function ($employee) use ($start_date, $end_date) {
                $all_parent_department_ids = $this->all_parent_departments_of_user($employee->id);

                // Process holiday dates
                $holiday_dates =  $this->holidayComponent->get_holiday_dates($start_date, $end_date, $employee->id, $all_parent_department_ids);

                $work_shift_histories = $this->workShiftHistoryComponent->get_work_shift_histories($start_date, $end_date, $employee->id);
                $weekend_dates = $this->holidayComponent->get_weekend_dates($start_date, $end_date, $employee->id, $work_shift_histories);

                 // Process already taken leave hourly dates
                $already_taken_leave_dates = $this->leaveComponent->get_already_taken_leave_dates($start_date, $end_date, $employee->id,false);

                // Merge the collections and remove duplicates
                $all_leaves_collection = collect($holiday_dates)->merge($weekend_dates)->merge($already_taken_leave_dates)->unique();


                // $result_collection now contains all unique dates from holidays and weekends
                $all_leaves_array = $all_leaves_collection->values()->all();




                $all_dates = collect(range(strtotime($start_date), strtotime($end_date), 86400)) // 86400 seconds in a day
                    ->map(function ($timestamp) {
                        return date('Y-m-d', $timestamp);
                    });



                $all_scheduled_dates = $all_dates->reject(fn ($date) => in_array($date, $all_leaves_array));



                $schedule_data = [];
                $total_capacity_hours = 0;


                $all_scheduled_dates->each(function ($date) use (&$schedule_data, &$total_capacity_hours, $employee) {

                    $work_shift_history =  $this->workShiftHistoryComponent->get_work_shift_history($date, $employee->id);
                    $work_shift_details =  $this->workShiftHistoryComponent->get_work_shift_details($work_shift_history, $date);

                    if ($work_shift_details) {
                        $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                        $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                        $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);


                        $schedule_data[] = [
                            "date" => Carbon::createFromFormat('Y-m-d', $date)->format('d-m-Y'),
                            "capacity_hours" => $capacity_hours,
                            "break_type" => $work_shift_history->break_type,
                            "break_hours" => $work_shift_history->break_hours,
                            "start_at" => $work_shift_details->start_at,
                            'end_at' => $work_shift_details->end_at,
                            'is_weekend' => $work_shift_details->is_weekend,
                        ];
                        $total_capacity_hours += $capacity_hours;
                    }
                });

                $employee->schedule_data = $schedule_data;
                $employee->total_capacity_hours = $total_capacity_hours;

                return $employee;
            });



            return response()->json($employees, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/get-recruitment-processes/{id}",
     *      operationId="getRecruitmentProcessesByUserId",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="start_date",
     *         required=true,
     *         example="start_date"
     *      ),
     *
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="end_date",
     *         required=true,
     *         example="end_date"
     *      ),

     *      summary="This method is to get user by id",
     *      description="This method is to get user by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getRecruitmentProcessesByUserId($id, Request $request)
    {
        //  $logPath = storage_path('logs');
        //  foreach (File::glob($logPath . '/*.log') as $file) {
        //      File::delete($file);
        //  }
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $user = User::with("roles")
                ->where([
                    "id" => $id
                ])
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                    return $query->where(function ($query) {
                        return  $query->where('created_by', auth()->user()->id)
                            ->orWhere('id', auth()->user()->id)
                            ->orWhere('business_id', auth()->user()->business_id);
                    });
                })
                ->first();

            if (!$user) {

                return response()->json([
                    "message" => "no user found"
                ], 404);
            }



            $user_recruitment_processes = UserRecruitmentProcess::with("recruitment_process")
                ->where([
                    "user_id" => $user->id
                ])
                ->whereNotNull("description")
                ->get();






            return response()->json($user_recruitment_processes, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }





    /**
     *
     * @OA\Delete(
     *      path="/v1.0/users/{ids}",
     *      operationId="deleteUsersByIds",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         description="ids",
     *         required=true,
     *  example="1,2,3"
     *      ),
     *      summary="This method is to delete user by ids",
     *      description="This method is to delete user by ids",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function deleteUsersByIds($ids, Request $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('user_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $idsArray = explode(',', $ids);
            $existingIds = User::whereIn('id', $idsArray)
                ->when(!$request->user()->hasRole('superadmin'), function ($query) {
                    return $query->where(function ($query) {
                        return  $query->where('created_by', auth()->user()->id)
                            ->orWhere('business_id', auth()->user()->business_id);
                    });
                })
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);

            if (!empty($nonExistingIds)) {
                return response()->json([
                    "message" => "Some or all of the specified data do not exist."
                ], 404);
            }
            // Check if any of the existing users are superadmins
            $superadminCheck = User::whereIn('id', $existingIds)->whereHas('roles', function ($query) {
                $query->where('name', 'superadmin');
            })->exists();

            if ($superadminCheck) {
                return response()->json([
                    "message" => "Superadmin user(s) cannot be deleted."
                ], 401);
            }
            $userCheck = User::whereIn('id', $existingIds)->where("id", auth()->user()->id)->exists();

            if ($userCheck) {
                return response()->json([
                    "message" => "You can not delete your self."
                ], 401);
            }

            User::whereIn('id', $existingIds)->forceDelete();
            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/generate/employee-id",
     *      operationId="generateEmployeeId",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },



     *      summary="This method is to generate employee id",
     *      description="This method is to generate employee id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */
    public function generateEmployeeId(Request $request)
    {
        $business = Business::where(["id" => $request->user()->business_id])->first();


        $prefix = "";
        if ($business) {
            preg_match_all('/\b\w/', $business->name, $matches);

            $prefix = implode('', array_map(function ($match) {
                return strtoupper($match[0]);
            }, $matches[0]));

            // If you want to get only the first two letters from each word:
            $prefix = substr($prefix, 0, 2 * count($matches[0]));
        }

        $current_number = 1; // Start from 0001

        do {
            $user_id = $prefix . "-" . str_pad($current_number, 4, '0', STR_PAD_LEFT);
            $current_number++; // Increment the current number for the next iteration
        } while (
            DB::table('users')->where([
                'user_id' => $user_id,
                "business_id" => $request->user()->business_id
            ])->exists()
        );

        error_log($user_id);
        return response()->json(["user_id" => $user_id], 200);
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/validate/employee-id/{user_id}",
     *      operationId="validateEmployeeId",
     *      tags={"user_management.employee"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="user_id",
     *         required=true,
     *  example="1"
     *      ),
     *    *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),


     *      summary="This method is to validate employee id",
     *      description="This method is to validate employee id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */
    public function validateEmployeeId($user_id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            $user_id_exists =  DB::table('users')->where(
                [
                    'user_id' => $user_id,
                    "business_id" => $request->user()->business_id
                ]
            )
                ->when(
                    !empty($request->id),
                    function ($query) use ($request) {
                        $query->whereNotIn("id", [$request->id]);
                    }
                )
                ->exists();



            return response()->json(["user_id_exists" => $user_id_exists], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/get/user-activity",
     *      operationId="getUserActivity",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *  * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *
     *
     *     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get user activity",
     *      description="This method is to get user activity",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getUserActivity(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$this->isModuleEnabled("user_activity")) {
                $this->storeError(
                    'Module is not enabled',
                    403,
                    "front end error",
                    "front end error"
                );
                return response()->json(['messege' => 'Module is not enabled'], 403);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            //  if (!$request->user()->hasPermissionTo('user_view')) {
            //      return response()->json([
            //          "message" => "You can not perform this action"
            //      ], 401);
            //  }

            $user =     User::where(["id" => $request->user_id])
                ->when((!auth()->user()->hasRole("superadmin") && auth()->user()->id != $request->user_id), function ($query) use ($all_manager_department_ids) {
                    $query->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                })





                ->first();
            if (!$user) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "User not found"
                ], 404);
            }




            $activity = ActivityLog::where("activity", "!=", "DUMMY activity")
                ->where("description", "!=", "DUMMY description")

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    $term = $request->search_key;
                    return $query->where(function ($subquery) use ($term) {
                        $subquery->where("activity", "like", "%" . $term . "%")
                            ->orWhere("description", "like", "%" . $term . "%");
                    });
                })



                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })

                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("id", "DESC");
                })
                ->select(
                    "api_url",
                    "activity",
                    "description",
                    "ip_address",
                    "request_method",
                    "device",
                    "created_at",
                    "updated_at",
                    "user",
                    "user_id",
                )

                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;

            return response()->json($activity, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
