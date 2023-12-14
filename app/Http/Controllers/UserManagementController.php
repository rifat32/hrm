<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuestUserRegisterRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Requests\MultipleFileUploadRequest;
use App\Http\Requests\UserStoreDetailsRequest;
use App\Http\Requests\UserUpdateProfileRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\UserActivityUtil;
use App\Mail\VerifyMail;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Business;
use App\Models\EmployeePassportDetail;
use App\Models\EmployeeSponsorship;
use App\Models\EmployeeVisaDetail;
use App\Models\Leave;
use App\Models\LeaveRecord;
use App\Models\SettingLeaveType;
use App\Models\User;
use App\Models\UserWorkShift;
use App\Models\WorkShift;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// eeeeee
class UserManagementController extends Controller
{
    use ErrorUtil, UserActivityUtil,BusinessUtil, ModuleUtil;

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
             $this->storeActivity($request, "DUMMY activity","DUMMY description");

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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
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
     * *            @OA\Property(property="employee_id", type="string", format="string",example="045674"),
     *
     *
     *              @OA\Property(property="gender", type="string", format="string",example="male"),
     *                @OA\Property(property="is_in_employee", type="boolean", format="boolean",example="1"),
     *               @OA\Property(property="designation_id", type="number", format="number",example="1"),
     *              @OA\Property(property="employment_status_id", type="number", format="number",example="1"),
     *               @OA\Property(property="salary_per_annum", type="string", format="string",example="10"),
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
     *      *  * @OA\Property(property="departments", type="string", format="array", example={1,2,3}),
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('user_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id = $request->user()->business_id;

            $request_data = $request->validated();


            if(!$request->user()->hasRole('superadmin') && $request_data["role"] == "superadmin"){
                $error =  [
                    "message" => "You can not create superadmin.",
             ];
                throw new Exception(json_encode($error),403);
            }

            if(!empty($request_data["employee_id"])) {
                $employee_id_exists =  DB::table( 'users' )->where([
                    'employee_id'=> $request_data['employee_id'],
                    "created_by" => $request->user()->id
                 ]
                 )->exists();
                 if ($employee_id_exists) {
                    $error =  [
                           "message" => "The given data was invalid.",
                           "errors" => ["employee_id"=>["The employee id has already been taken."]]
                    ];
                       throw new Exception(json_encode($error),422);
                   }
            }



            $check_role = $this->checkRole($request_data["role"]);
            if (!$check_role["ok"]) {
                return response()->json([
                    "message" => $check_role["message"]
                ], $check_role["status"]);
            }


            if(!empty($request_data["departments"])) {
                $check_department = $this->checkDepartments($request_data["departments"]);
                        if (!$check_department["ok"]) {
                            return response()->json([
                                "message" => $check_department["message"]
                            ], $check_department["status"]);
                        }
             }
             if(!empty($request_data["work_shift_id"])) {
                 $work_shift =  WorkShift::where([
                    "id" => $request_data["work_shift_id"],
                    "business_id" =>auth()->user()->business_id
                ])
                ->first();
                if(!$work_shift) {
                    return response()->json([
                        "message" => "no work shift found"
                    ], 403);
                }
               }



            $request_data['password'] = Hash::make($request['password']);
            $request_data['is_active'] = true;
            $request_data['remember_token'] = Str::random(10);


            if (!empty($business_id)) {
                $request_data['business_id'] = $business_id;
            }


            $user =  User::create($request_data);
            $user->departments()->sync($request_data['departments'],[]);
            $user->assignRole($request_data['role']);
            if(!empty($request_data["work_shift_id"])) {
                $work_shift->users()->attach($user->id);
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
     *      * *            @OA\Property(property="employee_id", type="string", format="string",example="045674"),
     *            @OA\Property(property="email", type="string", format="string",example="How was this?"),
     *    *    *            @OA\Property(property="image", type="string", format="string",example="...png"),
     *                @OA\Property(property="gender", type="string", format="string",example="male"),
     *                @OA\Property(property="is_in_employee", type="boolean", format="boolean",example="1"),
     *               @OA\Property(property="designation_id", type="number", format="number",example="1"),
     *              @OA\Property(property="employment_status_id", type="number", format="number",example="1"),
     *               @OA\Property(property="salary_per_annum", type="string", format="string",example="10"),
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

if(!empty($request_data["employee_id"])) {
    $employee_id_exists =  DB::table( 'users' )->where([
        'employee_id'=> $request_data['employee_id'],
        "business_id" => $request->user()->business_id
     ]
     )
     ->whereNotIn('id', [$request_data["id"]])->exists();
     if ($employee_id_exists) {
        $error =  [
               "message" => "The given data was invalid.",
               "errors" => ["employee_id"=>["The employee id has already been taken."]]
        ];
           throw new Exception(json_encode($error),422);
       }

}


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

            $check_role = $this->checkRole($request_data["role"]);
            if (!$check_role["ok"]) {
                return response()->json([
                    "message" => $check_role["message"]
                ], $check_role["status"]);
            }


            if(!empty($request_data["departments"])) {
                $check_department = $this->checkDepartments($request_data["departments"]);
                        if (!$check_department["ok"]) {
                            return response()->json([
                                "message" => $check_department["message"]
                            ], $check_department["status"]);
                        }
                }
                if(!empty($request_data["work_shift_id"])) {
                    $work_shift =  WorkShift::where([
                       "id" => $request_data["work_shift_id"],
                       "business_id" =>auth()->user()->business_id
                   ])
                   ->first();
                   if(!$work_shift) {
                       return response()->json([
                           "message" => "no work shift found"
                       ], 403);
                   }
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
                    'employee_id',
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
                    'salary',
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
            $user->departments()->sync($request_data['departments'],[]);
            $user->syncRoles([$request_data['role']]);
            if(!empty($request_data["work_shift_id"])) {
                UserWorkShift::where([
                    "user_id" => $user->id
                ])
                ->delete();
                $work_shift->users()->attach($user->id);
            }


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
     *      path="/v1.0/users/store-details",
     *      operationId="storeUserDetails",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user details",
     *      description="This method is to store user details",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *     @OA\Property(property="employee_id", type="number", format="integer", example="1"),
 *     @OA\Property(property="date_assigned", type="string", format="date", example="2023-12-05"),
 *     @OA\Property(property="expiry_date", type="string", format="date", example="2023-12-31"),
 *     @OA\Property(property="status", type="string", format="string", example="pending", enum={"pending", "approved", "denied", "visa_granted"}),
 *     @OA\Property(property="note", type="string", format="string", example="Additional note"),
 *     @OA\Property(property="passport", type="string", format="string", example={
 *    "passport_number": "ABC123",
 *    "passport_issue_date": "2023-01-01",
 *    "passport_expiry_date": "2024-01-01",
 *    "place_of_issue": "City",
 *    "visa": {
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
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('user_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();
             $request_data["created_by"] = $request->user()->id;
             if(!empty($request_data["passport"]))  {
                $request_data["passport"] += ["created_by" => $request_data["created_by"]];

             }
             if(!empty($request_data["passport"]["visa"]))  {
                $request_data["passport"]["visa"] += ["created_by" => $request_data["created_by"]];

             }

             $user = User::where([
                 "id" => $request["employee_id"]
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

                    "employee_id" => $request_data["employee_id"],



                ],

                collect($request_data)->only([
                    'employee_id',
                    'date_assigned',
                    'expiry_date',
                    'status',
                    'note',
                    'created_by'
                ])->toArray()

            //     [
            //     "employee_id" => $request_data["employee_id"],
            //     'date_assigned' =>  $request_data["date_assigned"],
            //     'expiry_date' =>  $request_data["expiry_date"],
            //     'status' =>  $request_data["status"],
            //     'note' =>  $request_data["note"],
            //     'created_by' =>  $request_data["created_by"],
            //    ]



            );



            if($request_data["status"] == "visa_granted"){
                $employee_passport_details  =  EmployeePassportDetail::updateOrCreate(
                    [
                        "employee_sponsorship_id" => $employee_sponsorship->id,
                    ],
                    collect($request_data["passport"])->only([
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
                    collect($request_data["passport"]["visa"])->only([
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


            $employee_sponsorship->load(['passport.visa']);

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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
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


            $this->storeActivity($request, "DUMMY activity","DUMMY description");

            $request_data = $request->validated();


            if (!empty($request_data['password'])) {
                $request_data['password'] = Hash::make($request_data['password']);
            } else {
                unset($request_data['password']);
            }
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

    public function getUsers(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $users = User::with("roles")
            ->whereNotIn('id', [$request->user()->id])
            ->when(!empty($request->role), function ($query) use ($request) {
                $rolesArray = explode(',', $request->role);
              return   $query->whereHas("roles", function($q) use ($rolesArray) {
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
            ->when(empty($request->user()->business_id)  , function ($query) use ($request) {
                if(empty($request->business_id)) {
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
                return $query->where('created_at', "<=", $request->end_date);
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
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('user_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }

             $users = User::with("roles")
             ->whereNotIn('id', [$request->user()->id])
             ->when(!empty($request->role), function ($query) use ($request) {
                 $rolesArray = explode(',', $request->role);
               return   $query->whereHas("roles", function($q) use ($rolesArray) {
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

            ->when(empty($request->user()->business_id)  , function ($query) use ($request) {
                if(empty($request->business_id)) {
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
                 return $query->where('created_at', "<=", $request->end_date);
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
     *      path="/v1.0/users/{id}",
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $user = User::with([
                "roles",
                "departments",
                "designation",
                "employment_status",

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
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('user_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }

             $user = User::with("roles")
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


             $leave_types =   SettingLeaveType::where([
                "business_id" => auth()->user()->business_id,
             ])->get();

             foreach($leave_types as $key=>$leave_type) {
                $total_recorded_hours = LeaveRecord::whereHas('leave', function ($query) use($user,$leave_type) {
                    $query->where([
                        "employee_id" =>$user->id,
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
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
            $userCheck = User::whereIn('id', $existingIds)->where("id",auth()->user()->id)->exists();

            if ($userCheck) {
                return response()->json([
                    "message" => "You can not delete your self."
                ], 401);
            }
            User::destroy($existingIds);
            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);




        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }




/**
 *
 * @OA\Get(
 *      path="/v1.0/users/generate/employee-id",
 *      operationId="generateEmployeeId",
 *      tags={"user_management"},
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
            $employee_id = $prefix . "-" . str_pad($current_number, 4, '0', STR_PAD_LEFT);
            $current_number++; // Increment the current number for the next iteration
        } while (
            DB::table('users')->where([
                'employee_id' => $employee_id,
                "business_id" => $request->user()->business_id
            ])->exists()
        );


return response()->json(["employee_id" => $employee_id],200);
 }


/**
*
* @OA\Get(
*      path="/v1.0/users/validate/employee-id/{employee_id}",
*      operationId="validateEmployeeId",
*      tags={"user_management"},
*       security={
*           {"bearerAuth": {}}
*       },

*              @OA\Parameter(
*         name="employee_id",
*         in="path",
*         description="employee_id",
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
public function validateEmployeeId($employee_id, Request $request)
{
   try {
       $this->storeActivity($request, "DUMMY activity","DUMMY description");

       $employee_id_exists =  DB::table( 'users' )->where([
          'employee_id'=> $employee_id,
          "business_id" => $request->user()->business_id
       ]
       )->exists();



return response()->json(["employee_id_exists" => $employee_id_exists],200);

   } catch (Exception $e) {
       error_log($e->getMessage());
       return $this->sendError($e, 500,$request);
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
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if(!$this->isModuleEnabled("user_activity")) {
                return response()->json(['error' => 'Module is not enabled'], 403);
             }




            //  if (!$request->user()->hasPermissionTo('user_view')) {
            //      return response()->json([
            //          "message" => "You can not perform this action"
            //      ], 401);
            //  }

             $users = ActivityLog::where('user_id', $request->user()->id)
             ->where("activity", "!=", "DUMMY activity")
             ->where("description", "!=", "DUMMY description")
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
                return $query->where('created_at', "<=", $request->end_date);
            })

            ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                return $query->orderBy("id", $request->order_by);
            }, function ($query) {
                return $query->orderBy("id", "DESC");
            })
            ->select( "api_url",
            "activity",
            "description",
            "ip_address",
            "request_method",
            "device")

            ->when(!empty($request->per_page), function ($query) use ($request) {
                return $query->paginate($request->per_page);
            }, function ($query) {
                return $query->get();
            });
           ;

             return response()->json($users, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

}
