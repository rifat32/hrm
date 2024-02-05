<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Exports\UsersExport;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\GuestUserRegisterRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Requests\MultipleFileUploadRequest;
use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Requests\UserCreateV2Request;
use App\Http\Requests\UserStoreDetailsRequest;
use App\Http\Requests\UserUpdateAddressRequest;
use App\Http\Requests\UserUpdateBankDetailsRequest;
use App\Http\Requests\UserUpdateEmergencyContactRequest;
use App\Http\Requests\UserUpdateJoiningDateRequest;
use App\Http\Requests\UserUpdateProfileRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UserUpdateV2Request;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\UserActivityUtil;
use App\Mail\VerifyMail;
use App\Models\ActivityLog;

use App\Models\Business;
use App\Models\Department;
use App\Models\EmployeeAddressHistory;
use App\Models\EmployeePassportDetail;
use App\Models\EmployeePassportDetailHistory;
use App\Models\EmployeeSponsorship;
use App\Models\EmployeeSponsorshipHistory;
use App\Models\EmployeeVisaDetail;
use App\Models\EmployeeVisaDetailHistory;
use App\Models\EmployeeWorkShiftHistory;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveRecord;
use App\Models\Role;
use App\Models\SettingLeaveType;
use App\Models\User;
use App\Models\UserWorkShift;
use App\Models\WorkShift;
use Carbon\Carbon;
use DateTime;
use Error;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\File;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
// eeeeee
class UserManagementController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, ModuleUtil;



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


            $request_data['password'] = Hash::make($request['password']);
            $request_data['is_active'] = true;
            $request_data['remember_token'] = Str::random(10);


            if (!empty($business_id)) {
                $request_data['business_id'] = $business_id;
            }


            $user =  User::create($request_data);

            $user->assignRole($request_data['role']);

            // $user->token = $user->createToken('Laravel Password Grant Client')->accessToken;

            // $default_work_shift = WorkShift::where([
            //       "business_id" => auth()->user()->id,
            //       "is_business_default" => 1
            // ])
            // ->first();
            // if(!$default_work_shift) {
            //     throw new Error("There is no default workshift for this business");
            //  }
            //   $default_work_shift->users()->attach($user->id);

            $user->roles = $user->roles->pluck('name');


            $this->loadDefaultSettingLeave($user->business_id);
            $this->loadDefaultAttendanceSetting($user->business_id);




            // $user->permissions  = $user->getAllPermissions()->pluck('name');
            // error_log("cccccc");
            // $data["user"] = $user;
            // $data["permissions"]  = $user->getAllPermissions()->pluck('name');
            // $data["roles"] = $user->roles->pluck('name');
            // $data["token"] = $token;
            return response($user, 201);
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

     *     @OA\Property(property="sponsorship_details", type="string", format="string", example={
     *    "date_assigned": "2023-01-01",
     *    "expiry_date": "2024-01-01",
     *    "status": "pending",
     *  *    "note": "pending",
     *  *    "certificate_number": "pending note",
     *  *    "current_certificate_status": "pending",
     * *  *    "is_sponsorship_withdrawn": 1
     * }),
     *       @OA\Property(property="visa_details", type="string", format="string", example={
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

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");


            return DB::transaction(function () use ($request) {

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





                $request_data['password'] = Hash::make($request['password']);
                $request_data['is_active'] = true;
                $request_data['remember_token'] = Str::random(10);


                if (!empty($business_id)) {
                    $request_data['business_id'] = $business_id;
                }


                $user =  User::create($request_data);
                $user->departments()->sync($request_data['departments'], []);
                $user->assignRole($request_data['role']);

                if (!empty($request_data["recruitment_processes"])) {
                    $user->recruitment_processes()->createMany($request_data["recruitment_processes"]);
                }



                if (in_array($request["immigration_status"], ['sponsored'])) {
                    if (!empty($request_data["sponsorship_details"])) {
                        $request_data["sponsorship_details"]["user_id"] = $user->id;
                        $request_data["sponsorship_details"]["business_id"] = $user->business_id;
                        $employee_sponsorship  =  EmployeeSponsorship::create($request_data["sponsorship_details"]);

                        $request_data["sponsorship_details"]["from_date"] = now();
                        $request_data["sponsorship_details"]["sponsorship_id"] = $employee_sponsorship->id;
                        $employee_sponsorship_history  =  EmployeeSponsorshipHistory::create($request_data["sponsorship_details"]);

                        $ten_years_ago = Carbon::now()->subYears(10);
                        EmployeeSponsorshipHistory::where('to_date', '<=', $ten_years_ago)->delete();
                    }
                }
                if (in_array($request["immigration_status"], ['immigrant', 'sponsored'])) {

                    if (!empty($request_data["passport_details"])) {
                        $request_data["passport_details"]["user_id"] = $user->id;
                        $request_data["passport_details"]["business_id"] = $user->business_id;
                        $employee_passport_details  =  EmployeePassportDetail::create($request_data["passport_details"]);
                        $request_data["passport_details"]["from_date"] = now();
                        $request_data["passport_details"]["passport_detail_id"] = $employee_passport_details->id;
                        $employee_passport_details_history  =  EmployeePassportDetailHistory::create($request_data["passport_details"]);

                        $ten_years_ago = Carbon::now()->subYears(10);
                        EmployeePassportDetailHistory::where('to_date', '<=', $ten_years_ago)->delete();
                    }
                    if (!empty($request_data["visa_details"]) && $request_data["is_active_visa_details"]) {
                        $request_data["visa_details"]["user_id"] = $user->id;
                        $request_data["visa_details"]["business_id"] = $user->business_id;
                        $employee_visa_details  =  EmployeeVisaDetail::create($request_data["visa_details"]);


                        $ten_years_ago = Carbon::now()->subYears(10);
                        EmployeeVisaDetailHistory::where('to_date', '<=', $ten_years_ago)->delete();

                        $request_data["visa_details"]["from_date"] = now();
                        $request_data["visa_details"]["visa_detail_id"] = $employee_visa_details->id;
                        $employee_visa_details_history  =  EmployeeVisaDetailHistory::create($request_data["visa_details"]);
                    }
                }


                if (!empty($request_data["work_shift_id"])) {
                    $work_shift =  WorkShift::where([
                        "id" => $request_data["work_shift_id"],

                    ])
                        ->first();
                    if (!$work_shift) {
                        throw new Exception("Work shift validation failed");
                    }
                    if (!$work_shift->is_active) {
                        $this->storeError(
                            ("Please activate the work shift named '" . $work_shift->name . "'")
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => ("Please activate the work shift named '" . $work_shift->name . "'")], 400);
                    }
                    $work_shift->users()->attach($user->id);




                    $employee_work_shift_history_data = $work_shift->toArray();

                    $employee_work_shift_history_data["work_shift_id"] = $work_shift->id;

                    $employee_work_shift_history_data["from_date"] = now();
                    $employee_work_shift_history_data["to_date"] = NULL;

                    $employee_work_shift_history =  EmployeeWorkShiftHistory::create($employee_work_shift_history_data);
                    $employee_work_shift_history->users()->attach($user->id);
                } else {
                    $default_work_shift = WorkShift::where([
                        "business_id" => auth()->user()->business_id,
                        "is_business_default" => 1
                    ])
                        ->first();
                    if (!$default_work_shift) {
                        throw new Exception("There is no default workshift for this business");
                    }

                    if (!$default_work_shift->is_active) {
                        $this->storeError(
                            ("Please activate the work shift named '" . $default_work_shift->name . "'")
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => ("Please activate the work shift named '" . $default_work_shift->name . "'")], 400);
                    }

                    $default_work_shift->users()->attach($user->id);
                }
                // $user->token = $user->createToken('Laravel Password Grant Client')->accessToken;


                $user->roles = $user->roles->pluck('name');



                // $user->permissions  = $user->getAllPermissions()->pluck('name');
                // error_log("cccccc");
                // $data["user"] = $user;
                // $data["permissions"]  = $user->getAllPermissions()->pluck('name');
                // $data["roles"] = $user->roles->pluck('name');
                // $data["token"] = $token;
                return response($user, 201);
            });
        } catch (Exception $e) {
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

            $user  =  tap(User::where($userQueryTerms))->update(
                collect($request_data)->only([
                    'first_Name',
                    'middle_Name',
                    'last_Name',
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

                ])->toArray()
            )
                // ->with("somthing")

                ->first();
            if (!$user) {
                $this->storeError(
                    "no data found"
                    ,
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
                    "no data found"
                    ,
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

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            return DB::transaction(function () use ($request) {
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



                if (!empty($request_data["work_shift_id"])) {
                    $work_shift =  WorkShift::where([
                        "id" => $request_data["work_shift_id"],
                        "business_id" => auth()->user()->business_id
                    ])
                        ->first();
                    if (!$work_shift) {
                        $this->storeError(
                            "no work shift found"
                            ,
                            403,
                            "front end error",
                            "front end error"
                           );
                        return response()->json([
                            "message" => "no work shift found"
                        ], 403);
                    }
                    if (!$work_shift->is_active) {
                        $this->storeError(
                            ("Please activate the work shift named '" . $work_shift->name . "'")
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => ("Please activate the work shift named '" . $work_shift->name . "'")], 400);
                    }
                }


                if (!empty($request_data['password'])) {
                    $request_data['password'] = Hash::make($request_data['password']);
                } else {
                    unset($request_data['password']);
                }
                $request_data['is_active'] = true;
                $request_data['remember_token'] = Str::random(10);



                $user_query  = User::where([
                    "id" => $request_data["id"],
                ]);



                $user  =  tap($user_query)->update(
                    collect($request_data)->only([
                        'first_Name',
                        'last_Name',
                        'middle_Name',
                        "color_theme_name",
                        'emergency_contact_details',
                        'gender',
                        'is_in_employee',
                        'designation_id',
                        'employment_status_id',
                        'joining_date',
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
                        'is_sponsorship_offered',
                        "immigration_status",

                        'work_location_id',


                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$user) {
                    $this->storeError(
                        "no data found"
                        ,
                        404,
                        "front end error",
                        "front end error"
                       );
                    return response()->json([
                        "message" => "no user found"
                    ], 404);
                }



                $three_years_ago = Carbon::now()->subYears(3);
                EmployeeSponsorshipHistory::where('to_date', '<=', $three_years_ago)->delete();




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









                $user->departments()->sync($request_data['departments'], []);
                $user->syncRoles([$request_data['role']]);

                if (!empty($request_data["recruitment_processes"])) {
                    $user->recruitment_processes()->delete();
                    $user->recruitment_processes()->createMany($request_data["recruitment_processes"]);
                }

                if (!empty($request_data["work_shift_id"])) {




                    $current_workshift =  WorkShift::where([
                        "id" => $request_data["work_shift_id"]
                    ])
                        ->whereHas('users', function ($query) use ($user) {

                            $query->where([
                                "users.id" => $user->id
                            ]);
                        })
                        ->first();


                    if (!$current_workshift) {

                        UserWorkShift::where([
                            "user_id" => $user->id
                        ])
                            ->delete();


                        $work_shift->users()->attach($user->id);



                        EmployeeWorkShiftHistory::where([
                            "to_date" => NULL
                        ])
                            ->whereHas('users', function ($query) use ($user) {

                                $query->where([
                                    "users.id" => $user->id
                                ]);
                            })
                            ->update([
                                "to_date" => now()
                            ]);


                        $employee_work_shift_history_data = $work_shift->toArray();
                        $employee_work_shift_history_data["work_shift_id"] = $work_shift->id;
                        $employee_work_shift_history_data["from_date"] = now();
                        $employee_work_shift_history_data["to_date"] = NULL;
                        $employee_work_shift_history =  EmployeeWorkShiftHistory::create($employee_work_shift_history_data);
                        $employee_work_shift_history->users()->attach($user->id);
                    }
                }



                if (in_array($request["immigration_status"], ['sponsored'])) {
                    if (!empty($request_data["sponsorship_details"])) {
                        $request_data["sponsorship_details"]["user_id"] = $user->id;


                        $employee_sponsorship_query  =  EmployeeSponsorship::where([
                            "user_id" =>  $request_data["sponsorship_details"]["user_id"]
                        ]);
                        $employee_sponsorship  = $employee_sponsorship_query->first();

                        if ($employee_sponsorship) {
                            $employee_sponsorship_query->update(collect($request_data["sponsorship_details"])->only([
                                //  'user_id',
                                'date_assigned',
                                'expiry_date',
                                'status',
                                'note',
                                "certificate_number",
                                "current_certificate_status",
                                "is_sponsorship_withdrawn",
                                // 'created_by'
                            ])->toArray());
                        } else {
                            $request_data["sponsorship_details"]["business_id"] = $user->business_id;
                            $employee_sponsorship  =  EmployeeSponsorship::create($request_data["sponsorship_details"]);
                        }





                        // history section

                        $ten_years_ago = Carbon::now()->subYears(10);
                        EmployeeSponsorshipHistory::where('to_date', '<=', $ten_years_ago)->delete();


                        $request_data["sponsorship_details"]["sponsorship_id"] = $employee_sponsorship->id;
                        $request_data["sponsorship_details"]["from_date"] = now();


                        $employee_sponsorship_history  =  EmployeeSponsorshipHistory::where([
                            "user_id" =>  $request_data["sponsorship_details"]["user_id"],
                            "to_date" => NULL
                        ])
                            ->latest('created_at')
                            ->first();

                        if ($employee_sponsorship_history) {
                            $fields_to_check = [
                                'date_assigned', 'expiry_date', 'status', 'note',  "certificate_number", "current_certificate_status", "is_sponsorship_withdrawn",

                            ];

                            $fields_changed = false; // Initialize to false
                            foreach ($fields_to_check as $field) {
                                $value1 = $employee_sponsorship_history->$field;
                                $value2 = $request_data["sponsorship_details"][$field];
                                if (in_array($field, ['date_assigned', 'expiry_date'])) {
                                    $value1 = (new Carbon($value1))->format('Y-m-d');
                                    $value2 = (new Carbon($value2))->format('Y-m-d');
                                }
                                if ($value1 !== $value2) {
                                    $fields_changed = true;
                                    break;
                                }
                            }




                            if (
                                $fields_changed
                            ) {
                                $employee_sponsorship_history->to_date = now();
                                $employee_sponsorship_history->save();
                                EmployeeSponsorshipHistory::create($request_data["sponsorship_details"]);
                            }
                        } else {
                            EmployeeSponsorshipHistory::create($request_data["sponsorship_details"]);
                        }

                        // end history section










                    }
                }


                if (in_array($request["immigration_status"], ['immigrant', 'sponsored'])) {

                    if (!empty($request_data["passport_details"])) {
                        $request_data["passport_details"]["user_id"] = $user->id;




                        $employee_passport_details_query  =  EmployeePassportDetail::where([
                            "user_id" =>  $request_data["passport_details"]["user_id"]
                        ]);

                        $employee_passport_details  =  $employee_passport_details_query->first();



                        if ($employee_passport_details) {
                            $employee_passport_details_query->update(collect($request_data["passport_details"])->only([
                                // "user_id",
                                'passport_number',
                                "passport_issue_date",
                                "passport_expiry_date",
                                "place_of_issue",
                                // 'created_by'
                            ])->toArray());
                        } else {
                            $request_data["passport_details"]["business_id"] = $user->business_id;
                            $employee_passport_details  =  EmployeePassportDetail::create($request_data["passport_details"]);
                        }









                        // history section

                        $ten_years_ago = Carbon::now()->subYears(10);
                        EmployeePassportDetailHistory::where('to_date', '<=', $ten_years_ago)->delete();


                        $request_data["passport_details"]["passport_detail_id"] = $employee_passport_details->id;
                        $request_data["passport_details"]["from_date"] = now();


                        $employee_passport_details_history  =  EmployeePassportDetailHistory::where([
                            "user_id" =>  $request_data["passport_details"]["user_id"],
                            "to_date" => NULL
                        ])
                            ->latest('created_at')
                            ->first();



                        if ($employee_passport_details_history) {
                            $fields_to_check = [
                                'passport_number', "passport_issue_date", "passport_expiry_date", "place_of_issue", "passport_detail_id",
                            ];
                            $fields_changed = false; // Initialize to false
                            foreach ($fields_to_check as $field) {
                                $value1 = $employee_passport_details_history->$field;
                                $value2 = $request_data["passport_details"][$field];
                                // Convert date strings to a common format for accurate comparison
                                if (in_array($field, ['passport_issue_date', 'passport_expiry_date'])) {
                                    $value1 = (new Carbon($value1))->format('Y-m-d');
                                    $value2 = (new Carbon($value2))->format('Y-m-d');
                                }
                                if ($value1 !== $value2) {
                                    $fields_changed = true;
                                    break; // Exit the loop early if any difference is found
                                }
                            }





                            if (
                                $fields_changed
                            ) {
                                $employee_passport_details_history->to_date = now();
                                $employee_passport_details_history->save();
                                EmployeePassportDetailHistory::create($request_data["passport_details"]);
                            }
                        } else {
                            EmployeePassportDetailHistory::create($request_data["passport_details"]);
                        }

                        // end history section










                    }
                    if (!empty($request_data["visa_details"]) && $request_data["is_active_visa_details"]) {

                        $request_data["visa_details"]["user_id"] = $user->id;


                        $employee_visa_details_query  =  EmployeeVisaDetail::where([
                            "user_id" =>  $request_data["visa_details"]["user_id"]
                        ]);

                        $employee_visa_details  =  $employee_visa_details_query->first();

                        if ($employee_visa_details) {
                            $employee_visa_details_query->update(collect($request_data["visa_details"])->only([
                                // 'user_id',
                                'BRP_number',
                                "visa_issue_date",
                                "visa_expiry_date",
                                "place_of_issue",
                                "visa_docs",
                                // 'created_by'
                            ])->toArray());
                        } else {
                            $request_data["visa_details"]["business_id"] = $user->business_id;
                            $employee_visa_details  =  EmployeeVisaDetail::create($request_data["visa_details"]);
                        }













                        // history section

                        $ten_years_ago = Carbon::now()->subYears(10);
                        EmployeeVisaDetailHistory::where('to_date', '<=', $ten_years_ago)->delete();


                        $request_data["visa_details"]["visa_detail_id"] = $employee_visa_details->id;
                        $request_data["visa_details"]["from_date"] = now();


                        $employee_visa_details_history  =  EmployeeVisaDetailHistory::where([
                            "user_id" =>  $request_data["visa_details"]["user_id"],
                            "to_date" => NULL
                        ])
                            ->latest('created_at')
                            ->first();

                        if ($employee_visa_details_history) {
                            $fields_to_check = [
                                'BRP_number', "visa_issue_date", "visa_expiry_date", "place_of_issue", "visa_docs",
                            ];



                            $fields_changed = false; // Initialize to false
                            foreach ($fields_to_check as $field) {
                                $value1 = $employee_visa_details_history->$field;
                                $value2 = $request_data["visa_details"][$field];
                                if (in_array($field, ['visa_issue_date', 'visa_expiry_date'])) {
                                    $value1 = (new Carbon($value1))->format('Y-m-d');
                                    $value2 = (new Carbon($value2))->format('Y-m-d');
                                }
                                if ($value1 !== $value2) {
                                    $fields_changed = true;
                                    break;
                                }
                            }









                            if (
                                $fields_changed
                            ) {
                                $employee_visa_details_history->to_date = now();
                                $employee_visa_details_history->save();
                                EmployeeVisaDetailHistory::create($request_data["visa_details"]);
                            }
                        } else {
                            EmployeeVisaDetailHistory::create($request_data["visa_details"]);
                        }

                        // end history section

                    }
                }








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
                    "no data found"
                    ,
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
                    "no data found"
                    ,
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




            $user = tap($user_query)->update(
                collect($request_data)->only([
                    'joining_date'
                ])->toArray()
            )
                // ->with("somthing")
                ->first();

            if (!$user) {
                $this->storeError(
                    "no data found"
                    ,
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
                    "no data found"
                    ,
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
     *      path="/v1.0/users/store-details",
     *      operationId="storeUserDetails",
     *      tags={"user_management.dont_use_now"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user details",
     *      description="This method is to store user details",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="user_id", type="number", format="integer", example="1"),
     *     @OA\Property(property="date_assigned", type="string", format="date", example="2023-12-05"),
     *     @OA\Property(property="expiry_date", type="string", format="date", example="2023-12-31"),
     *     @OA\Property(property="status", type="string", format="string", example="pending", enum={"pending", "approved", "denied", "visa_granted"}),
     *     @OA\Property(property="note", type="string", format="string", example="Additional note"),
     *     @OA\Property(property="passport_details", type="string", format="string", example={
     *    "passport_number": "ABC123",
     *    "passport_issue_date": "2023-01-01",
     *    "passport_expiry_date": "2024-01-01",
     *    "place_of_issue": "City",
     *    "visa_details": {
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
     *    }
     *

     *
     * })

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

    public function storeUserDetails(UserStoreDetailsRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();
            $request_data["created_by"] = $request->user()->id;
            if (!empty($request_data["passport_details"])) {
                $request_data["passport_details"] += ["created_by" => $request_data["created_by"]];
            }
            if (!empty($request_data["passport_details"]["visa_details"])) {
                $request_data["passport_details"]["visa_details"] += ["created_by" => $request_data["created_by"]];
            }

            $user = User::where([
                "id" => $request["user_id"]
            ])->first();


            if (!$request->user()->hasRole('superadmin') && $user->business_id != $request->user()->business_id && $user->created_by != $request->user()->id) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }

            if (!$request->user()->hasRole('superadmin') && $user->hasRole('superadmin')) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }

            $employee_sponsorship  =  EmployeeSponsorship::updateOrCreate(
                [
                    "user_id" => $request_data["user_id"],
                ],

                collect($request_data)->only([
                    'user_id',
                    'date_assigned',
                    'expiry_date',
                    'status',
                    'note',
                    'created_by'
                ])->toArray()

                //     [
                //     "user_id" => $request_data["user_id"],
                //     'date_assigned' =>  $request_data["date_assigned"],
                //     'expiry_date' =>  $request_data["expiry_date"],
                //     'status' =>  $request_data["status"],
                //     'note' =>  $request_data["note"],
                //     'created_by' =>  $request_data["created_by"],
                //    ]



            );



            if ($request_data["status"] == "visa_granted") {
                $employee_passport_details  =  EmployeePassportDetail::updateOrCreate(
                    [
                        "employee_sponsorship_id" => $employee_sponsorship->id,
                    ],
                    collect($request_data["passport_details"])->only([
                        'employee_sponsorship_id',
                        'passport_number',
                        "passport_issue_date",
                        "passport_expiry_date",
                        "place_of_issue",
                        'created_by'
                    ])->toArray()

                    //     [
                    //     "employee_sponsorship_id" => $employee_sponsorship->id,
                    //     'passport_number' =>  $request_data["passport_number"],
                    //     'passport_issue_date' =>  $request_data["passport_issue_date"],
                    //     'passport_expiry_date' =>  $request_data["passport_expiry_date"],
                    //     'place_of_issue' =>  $request_data["place_of_issue"],
                    //     'created_by' =>  $request_data["created_by"],
                    //    ]

                );


                $employee_visa_details  =  EmployeeVisaDetail::updateOrCreate(
                    [
                        "employee_passport_details_id" => $employee_passport_details->id,
                    ],
                    collect($request_data["passport_details"]["visa_details"])->only([
                        'employee_passport_details_id',
                        'BRP_number',
                        "visa_issue_date",
                        "visa_expiry_date",
                        "place_of_issue",
                        "visa_docs",
                        'created_by'
                    ])->toArray()
                    //     [
                    //     "employee_passport_details_id" => $employee_passport_details->id,
                    //     'BRP_number' =>  $request_data["BRP_number"],
                    //     'visa_issue_date' =>  $request_data["visa_issue_date"],
                    //     'visa_expiry_date' =>  $request_data["visa_expiry_date"],
                    //     'place_of_issue' =>  $request_data["place_of_issue"],
                    //     'visa_docs' =>  $request_data["visa_docs"],
                    //     'created_by' =>  $request_data["created_by"],
                    //    ]

                );
            }




            return response($employee_sponsorship, 200);
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
                    "no data found"
                    ,
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
                $this->storeError(
                    "no data found"
                    ,
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
     *    *
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
     *    *  *   @OA\Parameter(
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


            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
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
                ->when(!empty($request->employment_status_id), function ($query) use ($request) {
                    return $query->where('employment_status_id', ($request->employment_status_id));
                })
                ->when(!empty($request->immigration_status), function ($query) use ($request) {
                    return $query->where('immigration_status', ($request->immigration_status));
                })
                ->when(!empty($request->sponsorship_status), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorships.status", $request->sponsorship_status);
                    });
                })
                ->when(!empty($request->sponsorship_note), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorships.note", $request->sponsorship_note);
                    });
                })
                ->when(!empty($request->sponsorship_certificate_number), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorships.certificate_number", $request->sponsorship_certificate_number);
                    });
                })
                ->when(!empty($request->sponsorship_current_certificate_status), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorships.current_certificate_status", $request->sponsorship_current_certificate_status);
                    });
                })
                ->when(isset($request->sponsorship_is_sponsorship_withdrawn), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorships.is_sponsorship_withdrawn", intval($request->sponsorship_is_sponsorship_withdrawn));
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

                ->when(!empty($request->designation_id), function ($query) use ($request) {
                    return $query->where('designation_id', ($request->designation_id));
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
                        $query->where("employee_sponsorships.date_assigned", ">=", ($request->start_sponsorship_date_assigned));
                    });
                })
                ->when(!empty($request->end_sponsorship_date_assigned), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorships.date_assigned", "<=", ($request->end_sponsorship_date_assigned . ' 23:59:59'));
                    });
                })


                ->when(!empty($request->start_sponsorship_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorships.expiry_date", ">=", $request->start_sponsorship_expiry_date);
                    });
                })
                ->when(!empty($request->end_sponsorship_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request) {
                        $query->where("employee_sponsorships.expiry_date", "<=", $request->end_sponsorship_expiry_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->sponsorship_expires_in_day), function ($query) use ($request, $today) {
                    return $query->whereHas("sponsorship_details", function ($query) use ($request, $today) {
                        $query_day = Carbon::now()->addDays($request->sponsorship_expires_in_day);
                        $query->whereBetween("employee_sponsorships.expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                    });
                })



                ->when(!empty($request->start_passport_issue_date), function ($query) use ($request) {
                    return $query->whereHas("passport_details", function ($query) use ($request) {
                        $query->where("employee_passport_details.passport_issue_date", ">=", $request->start_passport_issue_date);
                    });
                })
                ->when(!empty($request->end_passport_issue_date), function ($query) use ($request) {
                    return $query->whereHas("passport_details", function ($query) use ($request) {
                        $query->where("employee_passport_details.passport_issue_date", "<=", $request->end_passport_issue_date . ' 23:59:59');
                    });
                })


                ->when(!empty($request->start_passport_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("passport_details", function ($query) use ($request) {
                        $query->where("employee_passport_details.passport_expiry_date", ">=", $request->start_passport_expiry_date);
                    });
                })
                ->when(!empty($request->end_passport_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("passport_details", function ($query) use ($request) {
                        $query->where("employee_passport_details.passport_expiry_date", "<=", $request->end_passport_expiry_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->passport_expires_in_day), function ($query) use ($request, $today) {
                    return $query->whereHas("passport_details", function ($query) use ($request, $today) {
                        $query_day = Carbon::now()->addDays($request->passport_expires_in_day);
                        $query->whereBetween("employee_passport_details.passport_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                    });
                })
                ->when(!empty($request->start_visa_issue_date), function ($query) use ($request) {
                    return $query->whereHas("visa_details", function ($query) use ($request) {
                        $query->where("employee_visa_details.visa_issue_date", ">=", $request->start_visa_issue_date);
                    });
                })
                ->when(!empty($request->end_visa_issue_date), function ($query) use ($request) {
                    return $query->whereHas("visa_details", function ($query) use ($request) {
                        $query->where("employee_visa_details.visa_issue_date", "<=", $request->end_visa_issue_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->start_visa_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("visa_details", function ($query) use ($request) {
                        $query->where("employee_visa_details.visa_expiry_date", ">=", $request->start_visa_expiry_date);
                    });
                })
                ->when(!empty($request->end_visa_expiry_date), function ($query) use ($request) {
                    return $query->whereHas("visa_details", function ($query) use ($request) {
                        $query->where("employee_visa_details.visa_expiry_date", "<=", $request->end_visa_expiry_date . ' 23:59:59');
                    });
                })
                ->when(!empty($request->visa_expires_in_day), function ($query) use ($request, $today) {
                    return $query->whereHas("visa_details", function ($query) use ($request, $today) {
                        $query_day = Carbon::now()->addDays($request->visa_expires_in_day);
                        $query->whereBetween("employee_visa_details.visa_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                    });
                })
                ->when(isset($request->doesnt_have_payrun), function ($query) use ($request) {
                    if(intval($request->doesnt_have_payrun)) {
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
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return $query->where(function ($query) {
                        return  $query->where('business_id', auth()->user()->business_id);
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

    public function getUsersV3(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }



            $users = User::with(
                [
                    "designation" => function ($query) {
                        $query->select(
                            'designations.id',
                            'designations.name',
                        );
                    },
                    "roles",
                    "sponsorship_details",
                    "passport_details",
                    "visa_details",
                    "recruitment_processes",
                    "work_location"



                ]

            )
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->whereNotIn('id', [$request->user()->id])
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
                ->select("users.*")
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
     *      *   *              @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),
     *
     *  * @OA\Parameter(
 *     name="employee_details",
 *     in="query",
 *     description="Employee Details",
 *     required=true,
 *     example="employee_details"
 * )
 * @OA\Parameter(
 *     name="leave_allowances",
 *     in="query",
 *     description="Leave Allowances",
 *     required=true,
 *     example="leave_allowances"
 * )
 * @OA\Parameter(
 *     name="attendances",
 *     in="query",
 *     description="Attendances",
 *     required=true,
 *     example="attendances"
 * )
 * @OA\Parameter(
 *     name="leaves",
 *     in="query",
 *     description="Leaves",
 *     required=true,
 *     example="leaves"
 * )
 * @OA\Parameter(
 *     name="documents",
 *     in="query",
 *     description="Documents",
 *     required=true,
 *     example="documents"
 * )
 * @OA\Parameter(
 *     name="assets",
 *     in="query",
 *     description="Assets",
 *     required=true,
 *     example="assets"
 * )
 * @OA\Parameter(
 *     name="educational_history",
 *     in="query",
 *     description="Educational History",
 *     required=true,
 *     example="educational_history"
 * )
 * @OA\Parameter(
 *     name="job_history",
 *     in="query",
 *     description="Job History",
 *     required=true,
 *     example="job_history"
 * )
 * @OA\Parameter(
 *     name="current_cos_details",
 *     in="query",
 *     description="Current COS Details",
 *     required=true,
 *     example="current_cos_details"
 * )
 * @OA\Parameter(
 *     name="current_passport_details",
 *     in="query",
 *     description="Current Passport Details",
 *     required=true,
 *     example="current_passport_details"
 * )
 * @OA\Parameter(
 *     name="current_visa_details",
 *     in="query",
 *     description="Current Visa Details",
 *     required=true,
 *     example="current_visa_details"
 * )
 * @OA\Parameter(
 *     name="address_details",
 *     in="query",
 *     description="Address Details",
 *     required=true,
 *     example="address_details"
 * )
 * @OA\Parameter(
 *     name="contact_details",
 *     in="query",
 *     description="Contact Details",
 *     required=true,
 *     example="contact_details"
 * )
 * @OA\Parameter(
 *     name="notes",
 *     in="query",
 *     description="Notes",
 *     required=true,
 *     example="notes"
 * )
 * @OA\Parameter(
 *     name="bank_details",
 *     in="query",
 *     description="Bank Details",
 *     required=true,
 *     example="bank_details"
 * )
 * @OA\Parameter(
 *     name="social_links",
 *     in="query",
 *     description="Social Links",
 *     required=true,
 *     example="social_links"
 * )

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
                $this->storeError(
                    "no data found"
                    ,
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

            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF','CSV' ])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    $pdf = PDF::loadView('pdf.user', ["user" => $user, "request" => $request]);
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));

                }
                elseif (strtoupper($request->response_type) === 'CSV') {

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
                $this->storeError(
                    "no data found"
                    ,
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
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
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
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }


            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }

            $leave_types =   SettingLeaveType::where(function ($query) use ($request, $created_by) {


                $query->where('setting_leave_types.business_id', NULL)
                    ->where('setting_leave_types.is_default', 1)
                    ->where('setting_leave_types.is_active', 1)
                    ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                        $q->whereIn("disabled_setting_leave_types.created_by", [$created_by]);
                    })
                    ->when(isset($request->is_active), function ($query) use ($request, $created_by) {
                        if (intval($request->is_active)) {
                            return $query->whereDoesntHave("disabled", function ($q) use ($created_by) {
                                $q->whereIn("disabled_setting_leave_types.business_id", [auth()->user()->business_id]);
                            });
                        }
                    })


                    ->orWhere(function ($query) use ($request, $created_by) {
                        $query->where('setting_leave_types.business_id', NULL)
                            ->where('setting_leave_types.is_default', 0)
                            ->where('setting_leave_types.created_by', $created_by)
                            ->where('setting_leave_types.is_active', 1)

                            ->when(isset($request->is_active), function ($query) use ($request) {
                                if (intval($request->is_active)) {
                                    return $query->whereDoesntHave("disabled", function ($q) {
                                        $q->whereIn("disabled_setting_leave_types.business_id", [auth()->user()->business_id]);
                                    });
                                }
                            });
                    })
                    ->orWhere(function ($query) use ($request) {
                        $query->where('setting_leave_types.business_id', auth()->user()->business_id)
                            ->where('setting_leave_types.is_default', 0)
                            ->when(isset($request->is_active), function ($query) use ($request) {
                                return $query->where('setting_leave_types.is_active', intval($request->is_active));
                            });;
                    });
            })




                ->get();

            foreach ($leave_types as $key => $leave_type) {
                $total_recorded_hours = LeaveRecord::whereHas('leave', function ($query) use ($user, $leave_type) {
                    $query->where([
                        "user_id" => $user->id,
                        "leave_type_id" => $leave_type->id

                    ]);
                })
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
        $logPath = storage_path('logs');

        foreach (File::glob($logPath . '/*.log') as $file) {
            File::delete($file);
        }
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }

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
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }


            $all_parent_department_ids = [];
            $assigned_departments = Department::whereHas("users", function ($query) use ($id) {
                $query->where("users.id", $id);
            })->get();


            foreach ($assigned_departments as $assigned_department) {
                $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
            }


            $today = Carbon::now()->startOfYear()->format('Y-m-d');
            $end_date_of_year = Carbon::now()->endOfYear()->format('Y-m-d');

            $holidays = Holiday::where([
                "business_id" => $user->business_id
            ])
                ->where('holidays.start_date', ">=", $today)
                ->where('holidays.end_date', "<=", $end_date_of_year . ' 23:59:59')
                ->where([
                    "is_active" => 1
                ])
                ->where(function ($query) use ($id, $all_parent_department_ids) {
                    $query->whereHas("users", function ($query) use ($id) {
                        $query->where([
                            "users.id" => $id
                        ]);
                    })
                        ->orWhereHas("departments", function ($query) use ($all_parent_department_ids) {
                            $query->whereIn("departments.id", $all_parent_department_ids);
                        })

                        ->orWhere(function ($query) {
                            $query->whereDoesntHave("users")
                                ->whereDoesntHave("departments");
                        });
                })


                ->get();




            Log::info(json_encode($holidays));




            $holiday_dates = $holidays->flatMap(function ($holiday) {
                $start_date = Carbon::parse($holiday->start_date);
                $end_date = Carbon::parse($holiday->end_date);

                if ($start_date->eq($end_date)) {
                    return [$start_date->format('d-m-Y')];
                }

                $date_range = $start_date->daysUntil($end_date->addDay());

                return $date_range->map(function ($date) {
                    return $date->format('d-m-Y');
                });
            });



            $work_shift =  WorkShift::whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
                ->first();
                if (!$work_shift) {
                    $this->storeError(
                        "Please define workshift first"
                        ,
                        400,
                        "front end error",
                        "front end error"
                       );
                    return response()->json(["message" => "Please define workshift first"], 400);
                }
            $weekends = $work_shift->details()->where([
                "is_weekend" => 1
            ])
                ->get();





            $weekend_dates = $weekends->flatMap(function ($weekend) use ($today, $end_date_of_year) {
                $day_of_week = $weekend->day;

                // Find the next occurrence of the specified day of the week
                $next_day = Carbon::parse($today)->copy()->next($day_of_week);

                $matching_days = [];

                // Loop through the days between today and the end date
                while ($next_day <= $end_date_of_year) {
                    $matching_days[] = $next_day->format('d-m-Y');
                    $next_day->addWeek(); // Move to the next week
                }

                return $matching_days;
            });





            $already_taken_leaves =  Leave::where([
                "user_id" => $user->id
            ])
                ->whereHas('records', function ($query) use ($today, $end_date_of_year) {
                    $query->where('leave_records.date', '>=', $today)
                        ->where('leave_records.date', '<=', $end_date_of_year . ' 23:59:59');
                })
                ->get();


            $already_taken_leave_dates = $already_taken_leaves->flatMap(function ($leave) {
                return $leave->records->map(function ($record) {
                    return Carbon::parse($record->date)->format('d-m-Y');
                });
            })->toArray();




            // Merge the collections and remove duplicates
            $result_collection = $holiday_dates->merge($weekend_dates)->merge($already_taken_leave_dates)->unique();


            // $result_collection now contains all unique dates from holidays and weekends
            $result_array = $result_collection->values()->all();
            Log::info(json_encode($result_collection));


            return response()->json($result_array, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/users/get-schedule-information/{id}",
     *      operationId="getScheduleInformationByUserId",
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

    public function getScheduleInformationByUserId($id, Request $request)
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

            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }

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
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "no user found"
                ], 404);
            }


            $all_parent_department_ids = [];
            $assigned_departments = Department::whereHas("users", function ($query) use ($id) {
                $query->where("users.id", $id);
            })->get();


            foreach ($assigned_departments as $assigned_department) {
                $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
            }




            $start_date = !empty($request->start_date) ? $request->start_date : Carbon::now()->startOfYear()->format('Y-m-d');
            $end_date = !empty($request->end_date) ? $request->end_date : Carbon::now()->endOfYear()->format('Y-m-d');



            $work_shift =  WorkShift::whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
                ->first();

            if (!$work_shift) {
                $this->storeError(
                    "Please define workshift first"
                    ,
                    400,
                    "front end error",
                    "front end error"
                   );
                return response()->json(["message" => "Please define workshift first"], 400);
            }
            if (!$work_shift->is_active) {
                $this->storeError(
                    ("Please activate the work shift named '" . $work_shift->name . "'")
                    ,
                    400,
                    "front end error",
                    "front end error"
                   );

                return response()->json(["message" => ("Please activate the work shift named '" . $work_shift->name . "'")], 400);
            }


            $holidays = Holiday::where([
                "business_id" => $user->business_id
            ])
                ->where('holidays.start_date', ">=", $start_date)
                ->where('holidays.end_date', "<=", $end_date . ' 23:59:59')
                ->where([
                    "is_active" => 1
                ])
                ->where(function ($query) use ($id, $all_parent_department_ids) {
                    $query->whereHas("users", function ($query) use ($id) {
                        $query->where([
                            "users.id" => $id
                        ]);
                    })
                        ->orWhereHas("departments", function ($query) use ($all_parent_department_ids) {
                            $query->whereIn("departments.id", $all_parent_department_ids);
                        })

                        ->orWhere(function ($query) {
                            $query->whereDoesntHave("users")
                                ->whereDoesntHave("departments");
                        });
                })


                ->get();









            $holiday_dates = $holidays->flatMap(function ($holiday) {
                $start_date = Carbon::parse($holiday->start_date);
                $end_date = Carbon::parse($holiday->end_date);

                if ($start_date->eq($end_date)) {
                    return [$start_date->format('Y-m-d')];
                }

                $date_range = $start_date->daysUntil($end_date->addDay());

                return $date_range->map(function ($date) {
                    return $date->format('Y-m-d');
                });
            });






            $weekends = $work_shift->details()->where([
                "is_weekend" => 1
            ])
                ->get();





            $weekend_dates = $weekends->flatMap(function ($weekend) use ($start_date, $end_date) {
                $day_of_week = $weekend->day;

                // Find the next occurrence of the specified day of the week
                $next_day = Carbon::parse($start_date)->copy()->next($day_of_week);

                $matching_days = [];

                // Loop through the days between today and the end date
                while ($next_day <= $end_date) {
                    $matching_days[] = $next_day->format('Y-m-d');
                    $next_day->addWeek(); // Move to the next week
                }

                return $matching_days;
            });





            $already_taken_leaves =  Leave::where([
                "user_id" => $user->id
            ])
                ->whereHas('records', function ($query) use ($start_date, $end_date) {
                    $query->where('leave_records.date', '>=', $start_date)
                        ->where('leave_records.date', '<=', $end_date . ' 23:59:59');
                })
                ->get();


            $already_taken_leave_dates = $already_taken_leaves->flatMap(function ($leave) {
                return $leave->records->map(function ($record) {
                    return Carbon::parse($record->date)->format('Y-m-d');
                });
            })->toArray();



            // Merge the collections and remove duplicates
            $all_leaves_collection = $holiday_dates->merge($weekend_dates)->merge($already_taken_leave_dates)->unique();


            // $result_collection now contains all unique dates from holidays and weekends
            $all_leaves_array = $all_leaves_collection->values()->all();












            $all_dates = collect(range(strtotime($start_date), strtotime($end_date), 86400)) // 86400 seconds in a day
                ->map(function ($timestamp) {
                    return date('Y-m-d', $timestamp);
                });



            $all_scheduled_dates = $all_dates->reject(fn ($date) => in_array($date, $all_leaves_array));



            $schedule_data = [];
            $total_capacity_hours = 0;

            // Fetch all work shift details outside the loop
            $work_shift_details = $work_shift->details()->where([
                "is_weekend" => 0
            ])
                ->get()
                ->keyBy('day');

            $all_scheduled_dates->each(function ($date) use ($work_shift_details, &$schedule_data, &$total_capacity_hours) {
                $day_number = Carbon::parse($date)->dayOfWeek;
                $work_shift_detail = $work_shift_details->get($day_number);

                if ($work_shift_detail) {
                    $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->start_at);
                    $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->end_at);
                    $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);

                    $schedule_data[] = [
                        "date" => Carbon::createFromFormat('Y-m-d', $date)->format('d-m-Y'),
                        "capacity_hours" => $capacity_hours,
                    ];
                    $total_capacity_hours += $capacity_hours;
                }
            });



            return response()->json([
                "schedule_data" => $schedule_data,
                "total_capacity_hours" => $total_capacity_hours

            ], 200);
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

            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = User::whereIn('id', $idsArray)
                ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($business_id) {
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
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
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
            User::destroy($existingIds);
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
            )->exists();



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
             if(!$this->isModuleEnabled("user_activity")) {
                $this->storeError(
                    'Module is not enabled'
                    ,
                    403,
                    "front end error",
                    "front end error"
                   );
                return response()->json(['messege' => 'Module is not enabled'], 403);
             }

            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }


            //  if (!$request->user()->hasPermissionTo('user_view')) {
            //      return response()->json([
            //          "message" => "You can not perform this action"
            //      ], 401);
            //  }

            $user =     User::where(["id" => $request->user_id])
                ->when(!auth()->user()->hasRole("superadmin"), function ($query) use ($all_manager_department_ids) {
                    $query->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                })




                ->first();
            if (!$user) {
                $this->storeError(
                    "no data found"
                    ,
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
