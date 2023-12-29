<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCreateRequest;
use App\Http\Requests\AttendanceMultipleCreateRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Http\Requests\AttendanceWeeklyCreateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SettingAttendance;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     *     @OA\Property(property="employee_id", type="number", format="number", example="1"),
     *     @OA\Property(property="in_time", type="string", format="string", example="00:44:00"),
     *     @OA\Property(property="out_time", type="string", format="string", example="12:44:00"),
     *     @OA\Property(property="in_date", type="string", format="date", example="2023-11-18"),
     * *     @OA\Property(property="does_break_taken", type="boolean", format="boolean", example="1")
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




                    $setting_attendance = SettingAttendance::where([
                        "business_id" => auth()->user()->business_id
                    ])
                    ->first();
                    if (!$setting_attendance) {
                        return response()->json(["message" => "Please define attendance setting first"], 400);
                    }
                    $work_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                        $query->where('users.id', $request_data["employee_id"]);
                    })->first();
                    if (!$work_shift) {
                        return response()->json(["message" => "Please define workshift first"], 400);
                    }

                    $day_number = Carbon::parse($request_data["in_date"])->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "day" => $day_number
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        $error =  [
                            "message" => ("No work shift details found  day" . $day_number),
                        ];
                        throw new Exception(json_encode($error), 400);
                    }
                    if ($work_shift_details->is_weekend) {
                        $error =  [
                            "message" => ("there is a weekend on date " . $request_data["in_date"]),
                        ];
                        throw new Exception(json_encode($error), 400);
                    }
                    $holiday =   Holiday::where([
                        "business_id" => auth()->user()->business_id
                    ])
                        ->where('holidays.start_date', "<=", $request_data["in_date"])
                        ->where('holidays.end_date', ">=", $request_data["in_date"] . ' 23:59:59')
                        ->first();

                    if ($holiday) {
                        if ($holiday->is_active) {
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

                    if(!empty($request_data["in_time"]) && !empty($request_data["out_time"])) {


                    $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                    $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                    $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);


                    $in_time = Carbon::createFromFormat('H:i:s', $request_data["in_time"]);
                    $out_time = Carbon::createFromFormat('H:i:s', $request_data["out_time"]);
                    $total_present_hours = $out_time->diffInHours($in_time);

                    $tolerance_time = $in_time->diffInHours($work_shift_start_at);


                    $work_hours_delta = $total_present_hours - $capacity_hours;
                    $total_paid_hours = $total_present_hours;





                    if(empty($setting_attendance->punch_in_time_tolerance)) {
                        $behavior = "regular";
                    }
                    else {
                        if($tolerance_time > $setting_attendance->punch_in_time_tolerance){
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
     *    @OA\Property(property="employee_id", type="number", format="number", example="1"),
     *    @OA\Property(property="attendance_details", type="string", format="array", example={
     * {
     *    "note" : "note",
     *    "in_geolocation":"in_geolocation",
     *      *    "out_geolocation":"out_geolocation",
     *    "in_time" : "08:44:00",
     * "out_time" : "12:44:00",
     * "in_date" : "2023-11-18",
     * "does_break_taken" : 1
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
                    return response()->json(["message" => "Please define attendance setting first"], 400);
                }
                $work_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                    $query->where('users.id', $request_data["employee_id"]);
                })->first();
                if (!$work_shift) {
                    return response()->json(["message" => "Please define workshift first"], 400);
                }




                $attendances_data = collect($request_data["attendance_details"])->map(function ($item) use ($request_data, $work_shift, $setting_attendance) {
                    $day_number = Carbon::parse($item["in_date"])->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "day" => $day_number
                    ])
                        ->first();

                    if (!$work_shift_details) {
                        $error =  [
                            "message" => ("No work shift details found  day" . $day_number),
                        ];
                        throw new Exception(json_encode($error), 400);
                    }



                    if ($work_shift_details->is_weekend) {
                        $error =  [
                            "message" => ("there is a weekend on date" . $item["in_date"]),
                        ];
                        throw new Exception(json_encode($error), 400);
                    }

                    $holiday =   Holiday::where([
                        "business_id" => auth()->user()->business_id
                    ])
                        ->where('holidays.start_date', "<=", $item["in_date"])
                        ->where('holidays.end_date', ">=", $item["in_date"] . ' 23:59:59')
                        ->first();

                    if ($holiday) {
                        if ($holiday->is_active) {
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



                    if(!empty($item["in_time"]) && !empty($item["out_time"])) {
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


                        if(empty($setting_attendance->punch_in_time_tolerance)) {
                            $behavior = "regular";
                        }
                        else {
                            if($tolerance_time > $setting_attendance->punch_in_time_tolerance){
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
                        "employee_id" => $request_data["employee_id"],
                        "business_id" => auth()->user()->business_id,
                        "is_active" => True,
                        "created_by" => auth()->user()->id,
                        "note" => $item["note"],
                        "in_geolocation" => $item["in_geolocation"],
                        "out_geolocation" => $item["out_geolocation"],
                        "in_time" => $item["in_time"],
                        "out_time" => $item["out_time"],
                        "in_date" => $item["in_date"],
                        "does_break_taken" => $item["does_break_taken"],
                        "capacity_hours" => $capacity_hours,
                        "work_hours_delta" => $work_hours_delta,
                        "break_type" => $work_shift->break_type,
                        "break_hours" => $work_shift->break_hours,
                        "total_paid_hours" => $total_paid_hours,
                        "regular_work_hours" => $regular_work_hours,
                    ];
                });


                $employee = User::where([
                    "id" => $request_data["employee_id"]
                ])
                    ->first();

                    if(!$employee) {
                         return response()->json([
                            "message" => "someting_went_wrong"
                         ]);
                    }



                $success =   $employee->attendances()->createMany($attendances_data);

                if ($success) {
                    // Retrieve the created attendance records
                    $created_attendances = Attendance::where('employee_id', $request_data["employee_id"])
                        ->whereIn('in_date', $attendances_data->pluck('in_date'))
                        ->get();

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
     *     @OA\Property(property="employee_id", type="number", format="number", example="1"),
     *     @OA\Property(property="in_time", type="string", format="string", example="00:44:00"),
     *     @OA\Property(property="out_time", type="string", format="string", example="12:44:00"),
     *     @OA\Property(property="in_date", type="string", format="date", example="2023-11-18"),
     *     @OA\Property(property="does_break_taken", type="boolean", format="boolean", example="1")

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

                $check_employee = $this->checkUser($request_data["employee_id"]);
                if (!$check_employee["ok"]) {
                    return response()->json([
                        "message" => $check_employee["message"]
                    ], $check_employee["status"]);
                }


                $attendance_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $attendance_prev = Attendance::where($attendance_query_params)
                    ->first();
                if (!$attendance_prev) {
                    return response()->json([
                        "message" => "no attendance found"
                    ], 404);
                }

                $setting_attendance = SettingAttendance::where([
                    "business_id" => auth()->user()->business_id
                ])
                ->first();
                if (!$setting_attendance) {
                    return response()->json(["message" => "Please define attendance setting first"], 400);
                }

                $work_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                    $query->where('users.id', $request_data["employee_id"]);
                })->first();
                if (!$work_shift) {
                    return response()->json(["message" => "Please define workshift first"], 400);
                }

                $day_number = Carbon::parse($request_data["in_date"])->dayOfWeek;
                $work_shift_details =  $work_shift->details()->where([
                    "day" => $day_number
                ])
                    ->first();
                if (!$work_shift_details) {
                    $error =  [
                        "message" => ("No work shift details found  day" . $day_number),
                    ];
                    throw new Exception(json_encode($error), 400);
                }
                if ($work_shift_details->is_weekend) {
                    $error =  [
                        "message" => ("there is a weekend on date" . $request_data["in_date"]),
                    ];
                    throw new Exception(json_encode($error), 400);
                }
                $holiday =   Holiday::where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->where('holidays.start_date', "<=", $request_data["in_date"])
                    ->where('holidays.end_date', ">=", $request_data["in_date"] . ' 23:59:59')
                    ->first();

                if ($holiday) {
                    if ($holiday->is_active) {
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

                if(!empty($request_data["in_time"]) && !empty($request_data["out_time"])) {

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

                if(empty($setting_attendance->punch_in_time_tolerance)) {
                    $behavior = "regular";
                }
                else {
                    if($tolerance_time > $setting_attendance->punch_in_time_tolerance){
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
                }  else {
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
                        'employee_id',
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
                        "regular_work_hours"

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

                return response($attendance, 201);
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
     * name="employee_id",
     * in="query",
     * description="employee_id",
     * required=true,
     * example="1"
     * ),
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
            ])

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
                   ->when(!empty($request->employee_id), function ($query) use ($request) {
                       return $query->where('employee_id', $request->employee_id);
                   })
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
                });;



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
     * name="employee_id",
     * in="query",
     * description="employee_id",
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
            $business_id =  $request->user()->business_id;
            $setting_attendance = SettingAttendance::where([
                "business_id" => auth()->user()->business_id
            ])
            ->first();
            if (!$setting_attendance) {
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
            ])

                ->where(
                    [
                        "attendances.business_id" => $business_id
                    ]
                )
                ->when(!empty($request->employee_id), function ($query) use ($request) {
                    return $query->where('attendances.employee_id', $request->employee_id);
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
                });;



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


            $total_available_hours = $data["data_highlights"]["total_schedule_hours"] - $data["data_highlights"]["total_leave_hours"];


            if ($total_available_hours == 0 || $data["data_highlights"]["total_leave_hours"] == 0) {
                $data["data_highlights"]["total_work_availability_per_centum"] = 0;
            } else {
                $data["data_highlights"]["total_work_availability_per_centum"] = ($total_available_hours / $data["data_highlights"]["total_leave_hours"]) * 100;
            }

            if(!empty($setting_attendance->work_availability_definition)) {
                if($data["data_highlights"]["total_work_availability_per_centum"] >= $setting_attendance->work_availability_definition) {
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
     * name="employee_id",
     * in="query",
     * description="employee_id",
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

    public function getAttendancesV3(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('leave_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $employees = User::with(
                [
                    'attendances' => function ($query) use ($request) {
                        $query->when(!empty($request->start_date), function ($query) use ($request) {
                            return $query->where('in_date', '>=', ($request->start_date . ' 00:00:00'));
                        })
                            ->when(!empty($request->end_date), function ($query) use ($request) {
                                return $query->where('in_date', '<=', ($request->end_date . ' 23:59:59'));
                            });
                    },
                    'departments' => function ($query) use ($request) {
                        $query->select("departments.name");
                    },




                ]
            )
                ->whereHas("attendances", function ($q) use ($request) {
                    $q->whereNotNull("employee_id")
                        ->when(!empty($request->employee_id), function ($q) use ($request) {
                            $q->where('employee_id', $request->employee_id);
                        })
                        ->when(!empty($request->start_date), function ($q) use ($request) {
                            $q->where('in_date', '>=', $request->start_date . ' 00:00:00');
                        })
                        ->when(!empty($request->end_date), function ($q) use ($request) {
                            $q->where('in_date', '<=', ($request->end_date . ' 23:59:59'));
                        });
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
                ->when(!empty($request->employee_id), function ($query) use ($request) {
                    return $query->whereHas("attendances", function ($q) use ($request) {
                        $q->where('employee_id', $request->employee_id);
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



                $employees->each(function ($employee) use ($dateArray) {
                    // Get leaves for the current employee


                    $total_paid_hours = 0;


                    $employee->datewise_attendanes = collect($dateArray)->map(function ($date) use ($employee, &$total_paid_hours) {
                        $attendance = $employee->attendances->first(function ($attendance) use (&$date){

                            $in_date = Carbon::parse($attendance->in_date)->format("Y-m-d");


                            return $in_date == $date;

                        });




                        if($attendance){
                            $total_paid_hours += $attendance->total_paid_hours;
                            return [

                                'date' => Carbon::parse($date)->format("d-m-Y"),
                                'is_present' => 1,
                                'paid_hours' => $attendance->total_paid_hours
                            ];
                        }

                        return  null;



                    })->filter()->values();

                    $employee->total_paid_hours = $total_paid_hours;
                    $employee->unsetRelation('attendances');
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
            $business_id =  $request->user()->business_id;
            $attendance =  Attendance::where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->first();
            if (!$attendance) {
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
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Attendance::where([
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
            Attendance::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
