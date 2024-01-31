<?php

namespace App\Http\Controllers;

use App\Exports\AttendancesExport;
use App\Http\Requests\AttendanceApproveRequest;
use App\Http\Requests\AttendanceCreateRequest;
use App\Http\Requests\AttendanceMultipleCreateRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Http\Requests\AttendanceWeeklyCreateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Attendance;
use App\Models\AttendanceHistory;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\Role;
use App\Models\SettingAttendance;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;



    /**
     *
     * @OA\Post(
     *      path="/v1.0/attendances",
     *      operationId="createAttendance",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store attendance",
     *      description="This method is to store attendance",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="note", type="string",  format="string", example="r"),
     *    *     @OA\Property(property="in_geolocation", type="string",  format="string", example="r"),
     *   *    *     @OA\Property(property="out_geolocation", type="string",  format="string", example="r"),
     *
     *     @OA\Property(property="user_id", type="number", format="number", example="1"),
     *     @OA\Property(property="in_time", type="string", format="string", example="00:44:00"),
     *     @OA\Property(property="out_time", type="string", format="string", example="12:44:00"),
     *     @OA\Property(property="in_date", type="string", format="date", example="2023-11-18"),
     * *     @OA\Property(property="does_break_taken", type="boolean", format="boolean", example="1"),
     *  *     @OA\Property(property="work_location_id", type="integer", format="int", example="1"),
     *     *  *     @OA\Property(property="project_id", type="integer", format="int", example="1")
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

    public function createAttendance(AttendanceCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('attendance_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;

                $request_data["status"] = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");




                $setting_attendance = SettingAttendance::where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->first();
                if (!$setting_attendance) {
                    $this->storeError(
                        "Please define attendance setting first",
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => "Please define attendance setting first"], 400);
                }
                if (!isset($setting_attendance->auto_approval)) {
                    if ($setting_attendance->auto_approval) {
                        $request_data["status"] = "approved";
                    }
                }
                $work_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                    $query->where('users.id', $request_data["user_id"]);
                })->first();
                if (!$work_shift) {
                    $this->storeError(
                        "Please define workshift first",
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => "Please define workshift first"], 400);
                }
                if (!$work_shift->is_active) {
                    $this->storeError(
                        ("Please activate the work shift named '" . $work_shift->name . "'"),
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => ("Please activate the work shift named '" . $work_shift->name . "'")], 400);
                }

                $day_number = Carbon::parse($request_data["in_date"])->dayOfWeek;
                $work_shift_details =  $work_shift->details()->where([
                    "day" => $day_number
                ])
                    ->first();
                if (!$work_shift_details && !auth()->user()->hasRole("business_owner")) {
                    $this->storeError(
                        ("No work shift details found  day" . $day_number),
                        400,
                        "front end error",
                        "front end error"
                    );
                    $error =  [
                        "message" => ("No work shift details found  day" . $day_number),
                    ];
                    throw new Exception(json_encode($error), 400);
                }
                if ($work_shift_details->is_weekend && !auth()->user()->hasRole("business_owner")) {
                    $this->storeError(
                        ("there is a weekend on date " . $request_data["in_date"]),
                        400,
                        "front end error",
                        "front end error"
                    );
                    $error =  [
                        "message" => ("there is a weekend on date " . $request_data["in_date"]),
                    ];
                    throw new Exception(json_encode($error), 400);
                }
                $all_parent_department_ids = [];
                $assigned_departments = Department::whereHas("users", function ($query) use ($request_data) {
                    $query->where("users.id", $request_data["user_id"]);
                })->get();


                foreach ($assigned_departments as $assigned_department) {
                    $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
                }
                $holiday =   Holiday::where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->where('holidays.start_date', "<=", $request_data["in_date"])
                    ->where('holidays.end_date', ">=", $request_data["in_date"] . ' 23:59:59')
                    ->where(function ($query) use ($request_data, $all_parent_department_ids) {
                        $query->whereHas("users", function ($query) use ($request_data) {
                            $query->where([
                                "users.id" => $request_data["user_id"]
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
                    ->first();

                if ($holiday) {
                    if ($holiday->is_active && !auth()->user()->hasRole("business_owner")) {
                        $this->storeError(
                            ("there is a holiday on date" . $request_data["in_date"]),
                            400,
                            "front end error",
                            "front end error"
                        );
                        $error =  [
                            "message" => ("there is a holiday on date" . $request_data["in_date"]),
                        ];
                        throw new Exception(json_encode($error), 400);
                    }
                }




                $request_data["behavior"] = "absent";
                $request_data["capacity_hours"] = 0;
                $request_data["work_hours_delta"] = 0;
                $request_data["total_paid_hours"] = 0;
                $request_data["regular_work_hours"] = 0;
                $request_data["break_type"] = $work_shift->break_type;
                $request_data["break_hours"] = $work_shift->break_hours;

                if (!empty($request_data["in_time"]) && !empty($request_data["out_time"])) {

                    $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                    $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                    $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);


                    $in_time = Carbon::createFromFormat('H:i:s', $request_data["in_time"]);
                    $out_time = Carbon::createFromFormat('H:i:s', $request_data["out_time"]);
                    $total_present_hours = $out_time->diffInHours($in_time);

                    $tolerance_time = $in_time->diffInHours($work_shift_start_at);


                    $work_hours_delta = $total_present_hours - $capacity_hours;
                    $total_paid_hours = $total_present_hours;





                    if (empty($setting_attendance->punch_in_time_tolerance)) {
                        $behavior = "regular";
                    } else {
                        if ($tolerance_time > $setting_attendance->punch_in_time_tolerance) {
                            $behavior = "late";
                        } else if ($tolerance_time < (-$setting_attendance->punch_in_time_tolerance)) {
                            $behavior = "early";
                        } else {
                            $behavior = "regular";
                        }
                    }





                    if ($request_data["does_break_taken"]) {
                        if ($work_shift->break_type == 'unpaid') {
                            $total_paid_hours -= $work_shift->break_hours;
                        }
                    }









                    if ($work_hours_delta > 0) {
                        $regular_work_hours =  $total_paid_hours - $work_hours_delta;
                    } else {
                        $regular_work_hours = $total_paid_hours;
                    }



                    $request_data["behavior"] = $behavior;
                    $request_data["capacity_hours"] = $capacity_hours;
                    $request_data["work_hours_delta"] = $work_hours_delta;
                    $request_data["total_paid_hours"] = $total_paid_hours;
                    $request_data["regular_work_hours"] = $regular_work_hours;
                }


                $attendance =  Attendance::create($request_data);



                $attendance_history_data = $attendance->toArray();
                $attendance_history_data['attendance_id'] = $attendance->id;
                $attendance_history_data['actor_id'] = auth()->user()->id;
                $attendance_history_data['action'] = "create";
                $attendance_history_data['attendance_created_at'] = $attendance->created_at;
                $attendance_history_data['attendance_updated_at'] = $attendance->updated_at;

                // $attendance_history = AttendanceHistory::create($attendance_history_data);









                return response($attendance, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Post(
     *      path="/v1.0/attendances/multiple",
     *      operationId="createMultipleAttendance",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store attendance",
     *      description="This method is to store attendance",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="user_id", type="number", format="number", example="1"),
     *    @OA\Property(property="attendance_details", type="string", format="array", example={
     * {
     *    "note" : "note",
     *    "in_geolocation":"in_geolocation",
     *      *    "out_geolocation":"out_geolocation",
     *    "in_time" : "08:44:00",
     * "out_time" : "12:44:00",
     * "in_date" : "2023-11-18",
     * "does_break_taken" : 1,
     * "work_location_id" : 1,
     * "project_id" : 1
     *
     *
     *
     * }
     *
     * }),

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

    public function createMultipleAttendance(AttendanceMultipleCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('attendance_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();




                $setting_attendance = SettingAttendance::where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->first();
                if (!$setting_attendance) {
                    $this->storeError(
                        "Please define attendance setting first",
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => "Please define attendance setting first"], 400);
                }
                $work_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                    $query->where('users.id', $request_data["user_id"]);
                })->first();

                if (!$work_shift) {
                    $this->storeError(
                        "Please define workshift first",
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => "Please define workshift first"], 400);
                }

                if (!$work_shift->is_active) {
                    $this->storeError(
                        ("Please activate the work shift named '" . $work_shift->name . "'"),
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => ("Please activate the work shift named '" . $work_shift->name . "'")], 400);
                }



                $attendances_data = collect($request_data["attendance_details"])->map(function ($item) use ($request_data, $work_shift, $setting_attendance) {

                    $day_number = Carbon::parse($item["in_date"])->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "day" => $day_number
                    ])
                        ->first();

                    if (!$work_shift_details && !auth()->user()->hasRole("business_owner")) {

                        $this->storeError(
                            ("No work shift details found  day" . $day_number),
                            400,
                            "front end error",
                            "front end error"
                        );
                        $error =  [
                            "message" => ("No work shift details found  day" . $day_number),
                        ];
                        throw new Exception(json_encode($error), 400);
                    }



                    if ($work_shift_details->is_weekend && !auth()->user()->hasRole("business_owner")) {
                        $this->storeError(
                            ("there is a weekend on date" . $item["in_date"]),
                            400,
                            "front end error",
                            "front end error"
                        );
                        $error =  [
                            "message" => ("there is a weekend on date" . $item["in_date"]),
                        ];
                        throw new Exception(json_encode($error), 400);
                    }


                    $all_parent_department_ids = [];
                    $assigned_departments = Department::whereHas("users", function ($query) use ($request_data) {
                        $query->where("users.id", $request_data["user_id"]);
                    })->get();


                    foreach ($assigned_departments as $assigned_department) {
                        $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
                    }
                    $holiday =   Holiday::where([
                        "business_id" => auth()->user()->business_id
                    ])
                        ->where('holidays.start_date', "<=", $item["in_date"])
                        ->where('holidays.end_date', ">=", $item["in_date"] . ' 23:59:59')
                        ->where(function ($query) use ($request_data, $all_parent_department_ids) {
                            $query->whereHas("users", function ($query) use ($request_data) {
                                $query->where([
                                    "users.id" => $request_data["user_id"]
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
                        ->first();

                    if ($holiday) {
                        if ($holiday->is_active && !auth()->user()->hasRole("business_owner")) {
                            $this->storeError(
                                ("there is a holiday on date" . $item["in_date"]),
                                400,
                                "front end error",
                                "front end error"
                            );
                            $error =  [
                                "message" => ("there is a holiday on date" . $item["in_date"]),
                            ];
                            throw new Exception(json_encode($error), 400);
                        }
                    }


                    $behavior = "absent";
                    $capacity_hours = 0;
                    $work_hours_delta = 0;
                    $total_paid_hours = 0;
                    $regular_work_hours = 0;
                    $status = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");

                    if (!isset($setting_attendance->auto_approval)) {
                        if ($setting_attendance->auto_approval) {
                            $status = "approved";
                        }
                    }

                    if (!empty($item["in_time"]) && !empty($item["out_time"])) {
                        $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                        $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                        $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);


                        $in_time = Carbon::createFromFormat('H:i:s', $item["in_time"]);
                        $out_time = Carbon::createFromFormat('H:i:s', $item["out_time"]);
                        $total_present_hours = $out_time->diffInHours($in_time);

                        $tolerance_time = $in_time->diffInHours($work_shift_start_at);

                        $work_hours_delta = $total_present_hours - $capacity_hours;

                        $total_paid_hours = $total_present_hours;
                        $behavior = "absent";


                        if (empty($setting_attendance->punch_in_time_tolerance)) {
                            $behavior = "regular";
                        } else {
                            if ($tolerance_time > $setting_attendance->punch_in_time_tolerance) {
                                $behavior = "late";
                            } else if ($tolerance_time < (-$setting_attendance->punch_in_time_tolerance)) {
                                $behavior = "early";
                            } else {
                                $behavior = "regular";
                            }
                        }

                        if ($item["does_break_taken"]) {
                            if ($work_shift->break_type == 'unpaid') {
                                $total_paid_hours -= $work_shift->break_hours;
                            }
                        }



                        if ($work_hours_delta > 0) {
                            $regular_work_hours =  $total_paid_hours - $work_hours_delta;
                        } else {
                            $regular_work_hours = $total_paid_hours;
                        }
                    }






                    return [
                        "behavior" => $behavior,
                        "user_id" => $request_data["user_id"],
                        "business_id" => auth()->user()->business_id,
                        "is_active" => True,
                        "created_by" => auth()->user()->id,
                        "note" => !empty($item["note"]) ? $item["note"] : "",
                        "in_geolocation" => !empty($item["in_geolocation"]) ? $item["in_geolocation"] : "",
                        "out_geolocation" => !empty($item["out_geolocation"]) ? $item["out_geolocation"] : "",
                        "in_time" => $item["in_time"],
                        "out_time" => $item["out_time"],
                        "work_location_id" => $item["work_location_id"],
                        "project_id" => $item["project_id"],
                        "in_date" => $item["in_date"],
                        "does_break_taken" => $item["does_break_taken"],
                        "capacity_hours" => $capacity_hours,
                        "work_hours_delta" => $work_hours_delta,
                        "break_type" => $work_shift->break_type,
                        "break_hours" => $work_shift->break_hours,
                        "total_paid_hours" => $total_paid_hours,
                        "regular_work_hours" => $regular_work_hours,
                        "status" => $status
                    ];
                });


                $employee = User::where([
                    "id" => $request_data["user_id"]
                ])
                    ->first();

                if (!$employee) {
                    return response()->json([
                        "message" => "someting_went_wrong"
                    ]);
                }



                $created_attendances = $employee->attendances()->createMany($attendances_data);
                if (!empty($created_attendances)) {
                    $attendance_history_data = [];


                    foreach ($created_attendances as $attendance) {
                        $history_entry = [
                            'attendance_id' => $attendance->id,
                            'actor_id' => auth()->user()->id,
                            'action' => 'create',
                            'attendance_created_at' => $attendance->created_at,
                            'attendance_updated_at' => $attendance->updated_at,
                        ];

                        $attendance_history_data[] = $history_entry + $attendance->toArray();
                    }

                    // $employee->attendance_histories()->createMany($attendance_history_data);



                    // Return the created attendance records in the response
                    return response(['attendances' => $created_attendances], 201);
                } else {
                    // Handle the case where records were not successfully created
                    return response(['error' => 'Failed to create attendance records'], 500);
                }


                return response([], 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/attendances",
     *      operationId="updateAttendance",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update attendance ",
     *      description="This method is to update attendance",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *     @OA\Property(property="note", type="string",  format="string", example="r"),
     *   *     @OA\Property(property="in_geolocation", type="string",  format="string", example="r"),
     *    *   *     @OA\Property(property="out_geolocation", type="string",  format="string", example="r"),
     *
     *     @OA\Property(property="user_id", type="number", format="number", example="1"),
     *     @OA\Property(property="in_time", type="string", format="string", example="00:44:00"),
     *     @OA\Property(property="out_time", type="string", format="string", example="12:44:00"),
     *     @OA\Property(property="in_date", type="string", format="date", example="2023-11-18"),
     *     @OA\Property(property="does_break_taken", type="boolean", format="boolean", example="1"),
     *     @OA\Property(property="work_location_id", type="integer", format="int", example="1"),
     * *     @OA\Property(property="project_id", type="integer", format="int", example="1")
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

    public function updateAttendance(AttendanceUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('attendance_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();




                $attendance_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $attendance_prev = Attendance::where($attendance_query_params)
                    ->first();
                if (!$attendance_prev) {
                    $this->storeError(
                        "no data found",
                        404,
                        "front end error",
                        "front end error"
                    );
                    return response()->json([
                        "message" => "no attendance found"
                    ], 404);
                }

                $setting_attendance = SettingAttendance::where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->first();
                if (!$setting_attendance) {
                    $this->storeError(
                        "Please define attendance setting first",
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => "Please define attendance setting first"], 400);
                }

                $work_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                    $query->where('users.id', $request_data["user_id"]);
                })->first();
                if (!$work_shift) {
                    $this->storeError(
                        "Please define workshift first",
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => "Please define workshift first"], 400);
                }
                if (!$work_shift->is_active) {
                    $this->storeError(
                        ("Please activate the work shift named '" . $work_shift->name . "'"),
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => ("Please activate the work shift named '" . $work_shift->name . "'")], 400);
                }
                $day_number = Carbon::parse($request_data["in_date"])->dayOfWeek;
                $work_shift_details =  $work_shift->details()->where([
                    "day" => $day_number
                ])
                    ->first();
                if (!$work_shift_details && !auth()->user()->hasRole("business_owner")) {
                    $this->storeError(
                        ("No work shift details found  day" . $day_number),
                        400,
                        "front end error",
                        "front end error"
                    );
                    $error =  [
                        "message" => ("No work shift details found  day" . $day_number),
                    ];
                    throw new Exception(json_encode($error), 400);
                }
                if ($work_shift_details->is_weekend && !auth()->user()->hasRole("business_owner")) {
                    $this->storeError(
                        ("there is a weekend on date" . $request_data["in_date"]),
                        400,
                        "front end error",
                        "front end error"
                    );
                    $error =  [
                        "message" => ("there is a weekend on date" . $request_data["in_date"]),
                    ];
                    throw new Exception(json_encode($error), 400);
                }

                $all_parent_department_ids = [];
                $assigned_departments = Department::whereHas("users", function ($query) use ($request_data) {
                    $query->where("users.id", $request_data["user_id"]);
                })->get();


                foreach ($assigned_departments as $assigned_department) {
                    $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
                }
                $holiday =   Holiday::where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->where('holidays.start_date', "<=", $request_data["in_date"])
                    ->where('holidays.end_date', ">=", $request_data["in_date"] . ' 23:59:59')
                    ->where(function ($query) use ($request_data, $all_parent_department_ids) {
                        $query->whereHas("users", function ($query) use ($request_data) {
                            $query->where([
                                "users.id" => $request_data["user_id"]
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
                    ->first();

                if ($holiday) {
                    if ($holiday->is_active && !auth()->user()->hasRole("business_owner")) {
                        $this->storeError(
                            ("there is a holiday on date" . $request_data["in_date"]),
                            400,
                            "front end error",
                            "front end error"
                        );
                        $error =  [
                            "message" => ("there is a holiday on date" . $request_data["in_date"]),
                        ];
                        throw new Exception(json_encode($error), 400);
                    }
                }

                $request_data["behavior"] = "absent";
                $request_data["capacity_hours"] = 0;
                $request_data["work_hours_delta"] = 0;
                $request_data["total_paid_hours"] = 0;
                $request_data["regular_work_hours"] = 0;
                $request_data["break_type"] = $work_shift->break_type;
                $request_data["break_hours"] = $work_shift->break_hours;

                if (!empty($request_data["in_time"]) && !empty($request_data["out_time"])) {

                    $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                    $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                    $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);


                    $in_time = Carbon::createFromFormat('H:i:s', $request_data["in_time"]);
                    $out_time = Carbon::createFromFormat('H:i:s', $request_data["out_time"]);
                    $total_present_hours = $out_time->diffInHours($in_time);

                    $tolerance_time = $in_time->diffInHours($work_shift_start_at);

                    $work_hours_delta = $total_present_hours - $capacity_hours;

                    $total_paid_hours = $total_present_hours;

                    $behavior = "absent";

                    if (empty($setting_attendance->punch_in_time_tolerance)) {
                        $behavior = "regular";
                    } else {
                        if ($tolerance_time > $setting_attendance->punch_in_time_tolerance) {
                            $behavior = "late";
                        } else if ($tolerance_time < (-$setting_attendance->punch_in_time_tolerance)) {
                            $behavior = "early";
                        } else {
                            $behavior = "regular";
                        }
                    }



                    if ($request_data["does_break_taken"]) {
                        if ($work_shift->break_type == 'unpaid') {
                            $total_paid_hours -= $work_shift->break_hours;
                        }
                    }


                    if ($work_hours_delta > 0) {
                        $regular_work_hours =  $total_paid_hours - $work_hours_delta;
                    } else {
                        $regular_work_hours = $total_paid_hours;
                    }


                    $request_data["behavior"] = $behavior;
                    $request_data["capacity_hours"] = $capacity_hours;
                    $request_data["work_hours_delta"] = $work_hours_delta;
                    $request_data["total_paid_hours"] = $total_paid_hours;
                    $request_data["regular_work_hours"] = $regular_work_hours;
                }

                $attendance  =  tap(Attendance::where($attendance_query_params))->update(
                    collect($request_data)->only([
                        'note',
                        'in_geolocation',
                        'out_geolocation',
                        'user_id',
                        'in_time',
                        'out_time',
                        'in_date',
                        'does_break_taken',
                        'behavior',
                        "capacity_hours",
                        "work_hours_delta",
                        "break_type",
                        "break_hours",
                        "total_paid_hours",
                        "regular_work_hours",
                        "work_location_id",
                        "project_id",

                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$attendance) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }



                $attendance_history_data = $attendance->toArray();
                $attendance_history_data['attendance_id'] = $attendance->id;
                $attendance_history_data['actor_id'] = auth()->user()->id;
                $attendance_history_data['action'] = "update";
                $attendance_history_data['attendance_created_at'] = $attendance->created_at;
                $attendance_history_data['attendance_updated_at'] = $attendance->updated_at;

                $attendance_history = AttendanceHistory::create($attendance_history_data);

                return response($attendance, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/attendances/approve",
     *      operationId="approveAttendance",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to approve attendances ",
     *      description="This method is to approve attendances",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="attendance_id", type="number", format="number", example="1"),
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

    public function approveAttendance(AttendanceApproveRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo("attendance_approve")) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();




                $attendance_query_params = [
                    "id" => $request_data["attendance_id"],
                    "business_id" => $business_id
                ];
                $attendance = Attendance::where($attendance_query_params)->first();
                if (!$attendance) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                $setting_attendance = SettingAttendance::where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->first();
                if (!$setting_attendance) {
                    $this->storeError(
                        "Please define attendance setting first",
                        400,
                        "front end error",
                        "front end error"
                    );
                    return response()->json(["message" => "Please define attendance setting first"], 400);
                }

                $user = User::where([
                    // "id" =>  $single_leave_approval->created_by
                    "id" =>  auth()->user()->id
                ])
                    ->first();




                $special_user = $setting_attendance->special_users()->where(["user_id" => $user->id])->first();
                if ($special_user) {
                    if ($request_data["is_approved"]) {
                        $attendance->status = "approved";
                    } else {
                        $attendance->status = "rejected";
                    }
                }

                $role_names = $user->getRoleNames()->toArray();


                $roles =  Role::whereIn("name", $role_names)->get();
                foreach ($roles as $role) {
                    $special_role = $setting_attendance->special_roles()->where(["role_id" => $role->id])->first();
                    if ($special_role) {
                        if ($request_data["is_approved"]) {
                            $attendance->status = "approved";
                        } else {
                            $attendance->status = "rejected";
                        }
                        break;
                    }
                }

                $attendance->save();


                $attendance_history_data = $attendance->toArray();
                $attendance_history_data['attendance_id'] = $attendance->id;
                $attendance_history_data['actor_id'] = auth()->user()->id;
                $attendance_history_data['action'] = "approve";
                $attendance_history_data['attendance_created_at'] = $attendance->created_at;
                $attendance_history_data['attendance_updated_at'] = $attendance->updated_at;

                $attendance_history = AttendanceHistory::create($attendance_history_data);

                return response($attendance, 200);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/attendances",
     *      operationId="getAttendances",
     *      tags={"attendances"},
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
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *     *   * *  @OA\Parameter(
     * name="work_location_id",
     * in="query",
     * description="work_location_id",
     * required=true,
     * example="1"
     * ),
     *  *   * *  @OA\Parameter(
     * name="department_id",
     * in="query",
     * description="department_id",
     * required=true,
     * example="1"
     * ),
     * @OA\Parameter(
     * name="project_id",
     * in="query",
     * description="project_id",
     * required=true,
     * example="1"
     * ),
     *  * @OA\Parameter(
     * name="work_location_id",
     * in="query",
     * description="work_location_id",
     * required=true,
     * example="1"
     * ),
     *     *  *   * *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="pending_approval"
     * ),
     *
     *
     *
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get attendances  ",
     *      description="This method is to get attendances ",
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

    public function getAttendances(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('attendance_view')) {
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

            $business_id =  $request->user()->business_id;
            $attendances = Attendance::with([
                "employee" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "employee.departments" => function ($query) {
                    $query->select('departments.id', 'departments.name');
                },
                "work_location",
                "project"
            ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })

                ->where(
                    [
                        "attendances.business_id" => $business_id
                    ]
                )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        // $query->where("attendances.name", "like", "%" . $term . "%")
                        //     ->orWhere("attendances.description", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('attendances.user_id', $request->user_id);
                })
                ->when(!empty($request->work_location_id), function ($query) use ($request) {
                    return $query->where('attendances.user_id', $request->work_location_id);
                })

                ->when(!empty($request->project_id), function ($query) use ($request) {
                    return $query->where('attendances.project_id', $request->project_id);
                })
                ->when(!empty($request->work_location_id), function ($query) use ($request) {
                    return $query->where('attendances.work_location_id', $request->work_location_id);
                })

                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('attendances.status', $request->status);
                })
                ->when(!empty($request->department_id), function ($query) use ($request) {
                    return $query->whereHas("employee.departments", function ($query) use ($request) {
                        $query->where("departments.id", $request->department_id);
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('attendances.in_date', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('attendances.in_date', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("attendances.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("attendances.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;

            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    $pdf = PDF::loadView('pdf.attendances', ["attendances" => $attendances]);
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'attendance') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new AttendancesExport($attendances), ((!empty($request->file_name) ? $request->file_name : 'attendance') . '.csv'));
                }
            } else {
                return response()->json($attendances, 200);
            }

            return response()->json($attendances, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v2.0/attendances",
     *      operationId="getAttendancesV2",
     *      tags={"attendances"},
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
     *     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get attendances  ",
     *      description="This method is to get attendances ",
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

    public function getAttendancesV2(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('attendance_view')) {
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
            $business_id =  $request->user()->business_id;
            $setting_attendance = SettingAttendance::where([
                "business_id" => auth()->user()->business_id
            ])
                ->first();
            if (!$setting_attendance) {
                $this->storeError(
                    "Please define attendance setting first",
                    400,
                    "front end error",
                    "front end error"
                );
                return response()->json(["message" => "Please define attendance setting first"], 400);
            }
            $attendances = Attendance::with([
                "employee" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "employee.departments" => function ($query) {
                    $query->select('departments.id', 'departments.name');
                },
                "work_location",
                "project"
            ])

                ->where(
                    [
                        "attendances.business_id" => $business_id
                    ]
                )
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('attendances.user_id', $request->user_id);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        // $query->where("attendances.name", "like", "%" . $term . "%")
                        //     ->orWhere("attendances.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('attendances.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('attendances.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("attendances.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("attendances.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });


            // $leave_records = LeaveRecord::whereHas("leave", function ($query) {
            //     $query->where("leaves.status","approved" );
            // })
            // ->when(!empty($request->user_id), function ($q) use ($request) {
            //     $q->whereHas('leave',    function ($query) use ($request) {
            //         $query->where("leaves.user_id",  $request->user_id);
            //     });
            // })
            // ->when(!empty($request->start_date), function ($q) use ($request) {
            //     $q->where('date', '>=', $request->start_date . ' 00:00:00');
            // })
            // ->when(!empty($request->end_date), function ($q) use ($request) {
            //     $q->where('date', '<=', ($request->end_date . ' 23:59:59'));
            // })
            // ->get();

            //  foreach ($attendances as $attendance) {
            //     $attendance->total_leave_hours = $attendance->records->sum(function ($record) {
            //      $startTime = Carbon::parse($record->start_time);
            //      $endTime = Carbon::parse($record->end_time);
            //      return $startTime->diffInHours($endTime);

            //     });

            //  }


            $data["data"] = $attendances;


            $data["data_highlights"] = [];

            $data["data_highlights"]["behavior"]["absent"] = $attendances->filter(function ($attendance) {
                return $attendance->behavior == "absent";
            })->count();
            $data["data_highlights"]["behavior"]["regular"] = $attendances->filter(function ($attendance) {
                return $attendance->behavior == "regular";
            })->count();
            $data["data_highlights"]["behavior"]["early"] = $attendances->filter(function ($attendance) {
                return $attendance->behavior == "early";
            })->count();
            $data["data_highlights"]["behavior"]["late"] = $attendances->filter(function ($attendance) {
                return $attendance->behavior == "late";
            })->count();


            $maxBehavior = max($data["data_highlights"]["behavior"]);

            $data["data_highlights"]["average_behavior"] = array_search($maxBehavior, $data["data_highlights"]["behavior"]);


            $data["data_highlights"]["total_schedule_hours"] = $attendances->sum('capacity_hours');

            $data["data_highlights"]["total_leave_hours"] = abs($attendances->filter(function ($attendance) {

                return $attendance->work_hours_delta < 0;
            })->sum("work_hours_delta"));


            // $data["data_highlights"]["leave_records"] = $leave_records->sum("leave_hours");





            $total_available_hours = $data["data_highlights"]["total_schedule_hours"] - $data["data_highlights"]["total_leave_hours"];


            if ($total_available_hours == 0 || $data["data_highlights"]["total_schedule_hours"] == 0) {
                $data["data_highlights"]["total_work_availability_per_centum"] = 0;
            } else {
                $data["data_highlights"]["total_work_availability_per_centum"] = ($total_available_hours / $data["data_highlights"]["total_schedule_hours"]) * 100;
            }

            if (!empty($setting_attendance->work_availability_definition)) {
                if ($data["data_highlights"]["total_work_availability_per_centum"] >= $setting_attendance->work_availability_definition) {
                    $data["data_highlights"]["work_availability"] = "good";
                } else {
                    $data["data_highlights"]["work_availability"] = "bad";
                }
            } else {
                $data["data_highlights"]["work_availability"] = "good";
            }





            $data["data_highlights"]["total_active_hours"] = $attendances->sum('total_paid_hours');


            $data["data_highlights"]["total_extra_hours"] = $attendances->filter(function ($attendance) {

                return $attendance->work_hours_delta > 0;
            })->sum("work_hours_delta");








            return response()->json($data, 200);


            return response()->json($attendances, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v3.0/attendances",
     *      operationId="getAttendancesV3",
     *      tags={"attendances"},
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
     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get attendances  ",
     *      description="This method is to get attendances ",
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

    public function getAttendancesV3(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('attendance_view')) {
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
            $business_id =  $request->user()->business_id;
            $employees = User::with(
                [
                    // 'attendances' => function ($query) use ($request) {
                    //     $query->when(!empty($request->start_date), function ($query) use ($request) {
                    //         return $query->where('in_date', '>=', ($request->start_date . ' 00:00:00'));
                    //     })
                    //         ->when(!empty($request->end_date), function ($query) use ($request) {
                    //             return $query->where('in_date', '<=', ($request->end_date . ' 23:59:59'));
                    //         });
                    // },

                    // 'departments' => function ($query) use ($request) {
                    //     $query->select("departments.name");
                    // },
                    // "work_location",

                    // "attendances.project"




                ]
            )
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })

                ->where(
                    [
                        "users.business_id" => $business_id
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
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("attendances", function ($q) use ($request) {
                        $q->where('user_id', $request->user_id);
                    });
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


            if ((!empty($request->start_date) && !empty($request->end_date))) {

                $startDate = Carbon::parse(($request->start_date . ' 00:00:00'));
                $endDate = Carbon::parse(($request->end_date . ' 23:59:59'));
                $dateArray = [];
                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    $dateArray[] = $date->format('Y-m-d');
                }

                // while ($startDate->lte($endDate)) {
                //     $dateArray[] = $startDate->toDateString();
                //     $startDate->addDay();
                // }

                $employee_ids = $employees->pluck("id");


                $leave_records = LeaveRecord::whereHas("leave.leave_type", function ($query)  {
                    $query->where("setting_leave_types.type", "paid");
                })
                    ->whereHas('leave',    function ($query) use ($employee_ids)  {
                        $query->whereIn("leaves.user_id",  $employee_ids)
                        ->where("leaves.status", "approved");
                    })
                    ->where('date', '>=', $request->start_date . ' 00:00:00')
                    ->where('date', '<=', ($request->end_date . ' 23:59:59'))
                    ->get();


                $attendances = Attendance::
                  where("attendances.status", "approved")
                ->whereIn('attendances.user_id', $employee_ids)
                ->where('attendances.in_date', '>=', $request->start_date . ' 00:00:00')
                ->where('attendances.in_date', '<=', ($request->end_date . ' 23:59:59'))
                ->get();

                $employees->each(function ($employee) use ($dateArray, $attendances, $leave_records) {
                    // Get leaves for the current employee


                    $all_parent_department_ids = [];
                    $assigned_departments = Department::whereHas("users", function ($query) use ($employee) {
                        $query->where("users.id", $employee->id);
                    })->get();
                    foreach ($assigned_departments as $assigned_department) {
                        $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
                    }
                    $work_shift =   WorkShift::whereHas('users', function ($query) use ($employee) {
                        $query->where('users.id', $employee->id);
                    })->first();

                    if (!$work_shift) {
                        return false;
                    }

                    if (!$work_shift->is_active) {
                        return false;
                    }


                    $work_shift_details = $work_shift->details()->get()->keyBy('day');









                    $total_paid_hours = 0;
                    $total_paid_leave_hours = 0;
                    $total_paid_holiday_hours = 0;
                    $total_leave_hours = 0;

                    $total_capacity_hours = 0;
                    $total_balance_hours = 0;


                    $employee->datewise_attendanes = collect($dateArray)->map(function ($date) use ($attendances, $leave_records, &$total_balance_hours, &$total_paid_hours, &$total_capacity_hours, &$total_leave_hours,&$total_paid_leave_hours,&$total_paid_holiday_hours, $employee, $work_shift, $all_parent_department_ids,$work_shift_details) {

                        $day_number = Carbon::parse($date)->dayOfWeek;

                        $work_shift_detail = $work_shift_details->get($day_number);
                        // $work_shift_details =  $work_shift->details()->where([
                        //     "day" => $day_number
                        // ])
                        //     ->first();


                        $is_weekend = 1;
                        $capacity_hours = 0;
                        if ($work_shift_detail) {
                            $is_weekend = $work_shift_detail->is_weekend;
                            $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->start_at);
                            $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->end_at);
                            $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                        }

                        if(!$is_weekend) {
                            $total_capacity_hours += $capacity_hours;
                        }
  // aaaa

                        $holiday = Holiday::where([
                            "business_id" => auth()->user()->business_id
                        ])
                            ->where('holidays.start_date', '>=', $date . ' 00:00:00')
                            ->where('holidays.end_date', '<=', ($date . ' 23:59:59'))
                            ->where([
                                "is_active" => 1
                            ])

                            ->where(function ($query) use ($employee, $all_parent_department_ids) {
                                $query->whereHas("users", function ($query) use ($employee) {
                                    $query->where([
                                        "users.id" => $employee->id
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
                            ->first();
                        $attendance = $attendances->first(function ($attendance) use ($date, $employee) {
                            $in_date = Carbon::parse($attendance->in_date)->format("Y-m-d");
                            return (($in_date == $date) && ($attendance->user_id == $employee->id));
                        });

                        $paid_leave_record = $leave_records->first(function ($leave_record) use ($date,$employee,&$total_leave_hours)  {
                            $leave_date = Carbon::parse($leave_record->date)->format("Y-m-d");
                            if(($leave_record->user_id != $employee->id) || ($date != $leave_date)) {
                                 return false;
                            }
                            $total_leave_hours += $leave_record->leave_hours;
                            if($leave_record->leave->leave_type->type != "paid") {
                                return false;
                            }
                           return true;
                        });


                        $result_is_present = 0;
                        $result_paid_hours = 0;
                        $result_balance_hours = 0;

                        if ($paid_leave_record) {
                            $paid_leave_hours =  $paid_leave_record->leave_hours;
                            $total_paid_leave_hours += $paid_leave_hours;
                            $result_paid_hours += $paid_leave_hours;
                            $total_paid_hours +=  $paid_leave_hours;
                        }
                        if ($holiday) {
                            $holiday_hours = $employee->weekly_contractual_hours / $employee->minimum_working_days_per_week;
                            // if ($is_weekend) {
                            //     $holiday_hours = $employee->weekly_contractual_hours / $employee->minimum_working_days_per_week;
                            // } else {
                            //     $holiday_hours = $capacity_hours;
                            // }




                            $total_paid_holiday_hours += $holiday_hours;
                            $result_paid_hours += $holiday_hours;
                            $total_paid_hours += $holiday_hours;
                        }
                        if ($attendance ) {
                            if($attendance->total_paid_hours > 0) {
                                $result_is_present = 1;
                                $total_attendance_hours = $attendance->total_paid_hours;

                                if ($paid_leave_record || $holiday || $is_weekend) {
                                    $result_balance_hours = $total_attendance_hours;
                                } elseif ($attendance->work_hours_delta > 0) {
                                    $result_balance_hours = $attendance->work_hours_delta;
                                }


                                $total_paid_hours += $total_attendance_hours;
                                $total_balance_hours += $result_balance_hours;
                            }

                        }

                        if ($paid_leave_record || $attendance || $holiday) {
                            return [
                                'date' => Carbon::parse($date)->format("d-m-Y"),
                                'is_present' => $result_is_present,
                                'paid_hours' => $result_paid_hours,
                                "result_balance_hours" => $result_balance_hours,
                                'capacity_hours' => $capacity_hours,
                            ];
                        }



                        return  null;
                    }
                    )
                    ->filter()
                    ->values();

                    $employee->total_balance_hours = $total_balance_hours;
                    $employee->total_leave_hours = $total_leave_hours;
                    $employee->total_paid_leave_hours = $total_paid_leave_hours;
                    $employee->total_paid_holiday_hours = $total_paid_holiday_hours;
                    $employee->total_paid_hours = $total_paid_hours;
                    $employee->total_capacity_hours = $total_capacity_hours;
                    return $employee;

                });
            }





            return response()->json($employees, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/attendances/{id}",
     *      operationId="getAttendanceById",
     *      tags={"attendances"},
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
     *      summary="This method is to get attendance by id",
     *      description="This method is to get attendance by id",
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


    public function getAttendanceById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('attendance_view')) {
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
            $business_id =  $request->user()->business_id;

            $attendance =  Attendance::where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->first();
            if (!$attendance) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($attendance, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/attendances/{ids}",
     *      operationId="deleteAttendancesByIds",
     *      tags={"attendances"},
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
     *      summary="This method is to delete attendance by id",
     *      description="This method is to delete attendance by id",
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

    public function deleteAttendancesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('attendance_delete')) {
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
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Attendance::where([
                "business_id" => $business_id
            ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->whereIn('id', $idsArray)
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);

            if (!empty($nonExistingIds)) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "Some or all of the specified data do not exist."
                ], 404);
            }

            $attendances =  Attendance::whereIn("id", $existingIds)->get();

            foreach ($attendances as $attendance) {
                $history_entry = [
                    'attendance_id' => $attendance->id,
                    'actor_id' => auth()->user()->id,
                    'action' => 'delete',
                    'attendance_created_at' => $attendance->created_at,
                    'attendance_updated_at' => $attendance->updated_at,
                ];

                $attendance_history_data = $history_entry + $attendance->toArray();
                // $attendance_history = AttendanceHistory::create($attendance_history_data);
            }









            Attendance::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
