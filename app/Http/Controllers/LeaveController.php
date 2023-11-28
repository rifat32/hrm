<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveApproveRequest;
use App\Http\Requests\LeaveBypassRequest;
use App\Http\Requests\LeaveCreateRequest;
use App\Http\Requests\LeaveUpdateRequest;
use App\Http\Requests\MultipleFileUploadRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\LeaveUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\Role;
use App\Models\SettingLeave;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil,LeaveUtil;

    /**
     *
     * @OA\Post(
     *      path="/v1.0/leaves/multiple-file-upload",
     *      operationId="createLeaveFileMultiple",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store multiple leave files",
     *      description="This method is to store multiple leave files",
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

    public function createLeaveFileMultiple(MultipleFileUploadRequest $request)
    {
        try {
            $this->storeActivity($request, "");

            $insertableData = $request->validated();

            $location =  config("setup-config.leave_files_location");

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
     *      path="/v1.0/leaves",
     *      operationId="createLeave",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store leave",
     *      description="This method is to store leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *   @OA\Property(property="leave_duration", type="string", format="string", example="single_day"),
     *   @OA\Property(property="day_type", type="string", format="string", example="first_half"),
     *   @OA\Property(property="leave_type_id", type="integer", format="int", example=2),
     *   @OA\Property(property="employee_id", type="integer", format="int", example=2),
     *   @OA\Property(property="date", type="string", format="date", example="2023-11-03"),
     *   @OA\Property(property="note", type="string", format="string", example="dfzg drfg"),
     *   @OA\Property(property="start_date", type="string", format="date", example="2023-11-22"),
     *   @OA\Property(property="end_date", type="string", format="date", example="2023-11-08"),
     *   @OA\Property(property="start_time", type="string", format="date-time", example="18:00:00"),
     *   @OA\Property(property="end_time", type="string", format="date-time", example="18:00:00"),
     *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})
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

    public function createLeave(LeaveCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('leave_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $request_data["status"] = "pending";

                $check_leave_type = $this->checkLeaveType($request_data["leave_type_id"]);
                if (!$check_leave_type["ok"]) {
                    return response()->json([
                        "message" => $check_leave_type["message"]
                    ], $check_leave_type["status"]);
                }


                $check_employee = $this->checkUser($request_data["employee_id"]);
                if (!$check_employee["ok"]) {
                    return response()->json([
                        "message" => $check_employee["message"]
                    ], $check_employee["status"]);
                }



                $wors_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                    $query->where('id', $request_data["employee_id"]);
                })->first();

                if (!$wors_shift) {
                    $department = Department::whereHas('users', function ($query) use ($request_data) {
                        $query->where('id', $request_data["employee_id"]);
                    })->first();

                    if (!$department) {
                        return response()->json(["message" => "Hey please specify department for the employee first!"], 400);
                    }

                    $all_department_ids = $department->all_parent_ids;

                    $work_shift = WorkShift::whereHas('departments', function ($query) use ($all_department_ids) {
                        $query->whereIn('id', $all_department_ids);
                    })->orderByRaw('FIELD(department_id, ' . implode(',', $all_department_ids) . ')')->first();
                    if (!$work_shift) {
                        return response()->json(["message" => "Please define workshift first"], 400);
                    }
                }
                $leave_record_data_list = [];
                if ($request_data["leave_duration"] == "single_day") {

                    $dateString = $request_data["date"];
                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "off" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        return response()->json(["message" => "No work shift details found"], 400);
                    }

                    if (!$work_shift_details->is_weekend) {
                        $leave_record_data["start_time"] = $work_shift_details->start_at;
                        $leave_record_data["end_time"] = $work_shift_details->end_at;
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                } else if ($request_data["leave_duration"] == "multiple_day") {

                    $start_date = Carbon::parse($request_data["start_date"]);
                    $end_date = Carbon::parse($request_data["end_date"]);


                    $leave_dates = [];
                    for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
                        $leave_dates[] = $date->toDateString();
                    }
                    foreach ($leave_dates as $leave_date) {
                        $dateString = $leave_date;
                        $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                        $work_shift_details =  $work_shift->details()->where([
                            "off" => $dayNumber
                        ])
                            ->first();
                        if (!$work_shift_details) {
                            return response()->json(["message" => "No work shift details found"], 400);
                        }

                        if (!$work_shift_details->is_weekend) {
                            $leave_record_data["start_time"] = $work_shift_details->start_at;
                            $leave_record_data["end_time"] = $work_shift_details->end_at;
                            $leave_record_data["date"] = $leave_date;
                            array_push($leave_record_data_list, $leave_record_data);
                        }
                    }
                } else if ($request_data["leave_duration"] == "half_day") {

                    $dateString = $request_data["date"];
                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "off" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        return response()->json(["message" => "No work shift details found"], 400);
                    }

                    if (!$work_shift_details->is_weekend) {
                        $start_at = $work_shift_details->start_at;
                        $end_at = $work_shift_details->end_at;
                        if ($request_data["day_type"] == "first_half") {
                            $middle_time = date("H:i:s", strtotime("($start_at + $end_at) / 2"));
                            $work_shift_details->start_at = $middle_time;
                        } elseif ($request_data["day_type"] == "last_half") {
                            $middle_time = date("H:i:s", strtotime("($start_at + $end_at) / 2"));
                            $work_shift_details->end_at = $middle_time;
                        }

                        $leave_record_data["start_time"] = $work_shift_details->start_at;
                        $leave_record_data["end_time"] = $work_shift_details->end_at;
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                } else if ($request_data["leave_duration"] == "hours") {

                    $dateString = $request_data["date"];
                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "off" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        return response()->json(["message" => "No work shift details found"], 400);
                    }
                    if (!$request_data["start_time"] < $work_shift_details->start_at) {
                        return response()->json(["message" => ("The employee does not start working at " . $request_data["start_time"])], 400);
                    }
                    if (!$request_data["end_time"] > $work_shift_details->end_at) {
                        return response()->json(["message" => ("The employee does not close working at " . $request_data["end_time"])], 400);
                    }

                    if (!$work_shift_details->is_weekend) {
                        $leave_record_data["start_time"] = $work_shift_details->start_at;
                        $leave_record_data["end_time"] = $work_shift_details->end_at;
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                }


                $leave =  Leave::create($request_data);
                $leave->records()->createMany($leave_record_data_list);




                return response($leave, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/leaves/approve",
     *      operationId="approveLeave",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to approve leave ",
     *      description="This method is to approve leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="leave_id", type="number", format="number", example="Updated Christmas"),
     *   @OA\Property(property="is_approved", type="boolean", format="boolean", example="1")


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

    public function approveLeave(LeaveApproveRequest $request)
    {

        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('leave_approve')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();
                $request_data["created_by"] = $request->user()->id;
                $leave_approval =  LeaveApproval::create($request_data);
                if (!$leave_approval) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }


            $process_leave_approval =   $this->processLeaveApproval($request_data["leave_id"]);
            if(!$process_leave_approval["success"]) {
                return response([
                    "message" => $process_leave_approval["message"]
                ], $process_leave_approval["status"]);
            }



                return response($leave_approval, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
  /**
     *
     * @OA\Put(
     *      path="/v1.0/leaves/bypass",
     *      operationId="bypassLeave",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to approve leave ",
     *      description="This method is to approve leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="leave_id", type="number", format="number", example="Updated Christmas")
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

     public function bypassLeave(LeaveBypassRequest $request)
     {

         try {
             $this->storeActivity($request, "");
             return DB::transaction(function () use ($request) {
                 if (!$request->user()->hasPermissionTo('leave_approve')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }

                 $request_data = $request->validated();
                 $request_data["created_by"] = $request->user()->id;

                 $setting_leave = SettingLeave::where([
                    "business_id" => auth()->user()->business_id,
                    "is_default" => 0
                ])->first();

                if(!$setting_leave->allow_bypass){
                    return response([
                        "message" => "bypass not allowed"
                    ], 400);
                }

                $leave = Leave::where([
                    "id" => $request_data["leave_id"],
                    "business_id" => auth()->user()->business_id
                ])
                    ->first();

                if (!$leave) {
                    return response([
                        "message" => "no leave found"
                    ], 400);
                }
                $leave->status = "approved";

                $leave->save();


                return response($leave, 200);

             });
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/leaves",
     *      operationId="updateLeave",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update leave ",
     *      description="This method is to update leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *   @OA\Property(property="leave_duration", type="string", format="string", example="single_day"),
     *   @OA\Property(property="day_type", type="string", format="string", example="first_half"),
     *   @OA\Property(property="leave_type_id", type="integer", format="int", example=2),
     *   @OA\Property(property="employee_id", type="integer", format="int", example=2),
     *   @OA\Property(property="date", type="string", format="date", example="2023-11-03"),
     *   @OA\Property(property="note", type="string", format="string", example="dfzg drfg"),
     *   @OA\Property(property="start_date", type="string", format="date", example="2023-11-22"),
     *   @OA\Property(property="end_date", type="string", format="date", example="2023-11-08"),
     *   @OA\Property(property="start_time", type="string", format="date-time", example="18:00:00"),
     *   @OA\Property(property="end_time", type="string", format="date-time", example="18:00:00"),
     *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})

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

    public function updateLeave(LeaveUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('leave_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();

                $check_leave_type = $this->checkLeaveType($request_data["leave_type_id"]);
                if (!$check_leave_type["ok"]) {
                    return response()->json([
                        "message" => $check_leave_type["message"]
                    ], $check_leave_type["status"]);
                }


                $check_employee = $this->checkUser($request_data["employee_id"]);
                if (!$check_employee["ok"]) {
                    return response()->json([
                        "message" => $check_employee["message"]
                    ], $check_employee["status"]);
                }


                $leave_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $leave_prev = Leave::where($leave_query_params)
                    ->first();
                if (!$leave_prev) {
                    return response()->json([
                        "message" => "no leave found"
                    ], 404);
                }

                $wors_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                    $query->where('id', $request_data["employee_id"]);
                })->first();

                if (!$wors_shift) {
                    $department = Department::whereHas('users', function ($query) use ($request_data) {
                        $query->where('id', $request_data["employee_id"]);
                    })->first();

                    if (!$department) {
                        return response()->json(["message" => "Hey please specify department for the employee first!"], 400);
                    }

                    $all_department_ids = $department->all_parent_ids;

                    $work_shift = WorkShift::whereHas('departments', function ($query) use ($all_department_ids) {
                        $query->whereIn('id', $all_department_ids);
                    })->orderByRaw('FIELD(department_id, ' . implode(',', $all_department_ids) . ')')->first();
                    if (!$work_shift) {
                        return response()->json(["message" => "Please define workshift first"], 400);
                    }
                }
                $leave_record_data_list = [];
                if ($request_data["leave_duration"] == "single_day") {

                    $dateString = $request_data["date"];
                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "off" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        return response()->json(["message" => "No work shift details found"], 400);
                    }

                    if (!$work_shift_details->is_weekend) {
                        $leave_record_data["start_time"] = $work_shift_details->start_at;
                        $leave_record_data["end_time"] = $work_shift_details->end_at;
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                } else if ($request_data["leave_duration"] == "multiple_day") {

                    $start_date = Carbon::parse($request_data["start_date"]);
                    $end_date = Carbon::parse($request_data["end_date"]);


                    $leave_dates = [];
                    for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
                        $leave_dates[] = $date->toDateString();
                    }
                    foreach ($leave_dates as $leave_date) {
                        $dateString = $leave_date;
                        $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                        $work_shift_details =  $work_shift->details()->where([
                            "off" => $dayNumber
                        ])
                            ->first();
                        if (!$work_shift_details) {
                            return response()->json(["message" => "No work shift details found"], 400);
                        }

                        if (!$work_shift_details->is_weekend) {
                            $leave_record_data["start_time"] = $work_shift_details->start_at;
                            $leave_record_data["end_time"] = $work_shift_details->end_at;
                            $leave_record_data["date"] = $leave_date;
                            array_push($leave_record_data_list, $leave_record_data);
                        }
                    }
                } else if ($request_data["leave_duration"] == "half_day") {

                    $dateString = $request_data["date"];
                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "off" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        return response()->json(["message" => "No work shift details found"], 400);
                    }

                    if (!$work_shift_details->is_weekend) {
                        $start_at = $work_shift_details->start_at;
                        $end_at = $work_shift_details->end_at;
                        if ($request_data["day_type"] == "first_half") {
                            $middle_time = date("H:i:s", strtotime("($start_at + $end_at) / 2"));
                            $work_shift_details->start_at = $middle_time;
                        } elseif ($request_data["day_type"] == "last_half") {
                            $middle_time = date("H:i:s", strtotime("($start_at + $end_at) / 2"));
                            $work_shift_details->end_at = $middle_time;
                        }

                        $leave_record_data["start_time"] = $work_shift_details->start_at;
                        $leave_record_data["end_time"] = $work_shift_details->end_at;
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                } else if ($request_data["leave_duration"] == "hours") {

                    $dateString = $request_data["date"];
                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "off" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        return response()->json(["message" => "No work shift details found"], 400);
                    }
                    if (!$request_data["start_time"] < $work_shift_details->start_at) {
                        return response()->json(["message" => ("The employee does not start working at " . $request_data["start_time"])], 400);
                    }
                    if (!$request_data["end_time"] > $work_shift_details->end_at) {
                        return response()->json(["message" => ("The employee does not close working at " . $request_data["end_time"])], 400);
                    }

                    if (!$work_shift_details->is_weekend) {
                        $leave_record_data["start_time"] = $work_shift_details->start_at;
                        $leave_record_data["end_time"] = $work_shift_details->end_at;
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                }


                $leave  =  tap(Leave::where($leave_query_params))->update(
                    collect($request_data)->only([
                        'leave_duration',
                        'day_type',
                        'leave_type_id',
                        'employee_id',
                        'date',
                        'note',
                        'start_date',
                        'end_date',
                        'start_time',
                        'end_time',
                        'attachments',
                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$leave) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }
                $leave->records()->delete();
                $leave->records()->createMany($leave_record_data_list);
                return response($leave, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/leaves",
     *      operationId="getLeaves",
     *      tags={"leaves"},
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
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get leaves  ",
     *      description="This method is to get leaves ",
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

    public function getLeaves(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('leave_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $leaves = Leave::where(
                [
                    "leaves.business_id" => $business_id
                ]
            )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        // $query->where("leaves.name", "like", "%" . $term . "%")
                        //     ->orWhere("leaves.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('leaves.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('leaves.created_at', "<=", $request->end_date);
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("leaves.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("leaves.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($leaves, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/leaves/{id}",
     *      operationId="getLeaveById",
     *      tags={"leaves"},
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
     *      summary="This method is to get leave by id",
     *      description="This method is to get leave by id",
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


    public function getLeaveById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('leave_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $leave =  Leave::where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->first();
            if (!$leave) {
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($leave, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/leaves/{ids}",
     *      operationId="deleteLeavesByIds",
     *      tags={"leaves"},
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
     *      summary="This method is to delete leave by id",
     *      description="This method is to delete leave by id",
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

    public function deleteLeavesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('leave_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Leave::where([
                "business_id" => $business_id
            ])
                ->whereIn('id', $idsArray)
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
            Leave::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
