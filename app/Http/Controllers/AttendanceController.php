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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
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
                    throw new Exception(json_encode($error),400);
                }
                if($work_shift_details->is_weekend){
                    $error =  [
                        "message" => ("there is a weekend on date" . $request_data["in_date"]),
                 ];
                    throw new Exception(json_encode($error),400);
                }
                $holiday =   Holiday::where([
                    "business_id" => auth()->user()->business_id
                ])
                ->where('holidays.start_date', "<=", $request_data["in_date"])
                ->where('holidays.end_date', ">=", $request_data["in_date"] . ' 23:59:59')
                ->first();

                if($holiday){
                    if($holiday->is_active) {
                        $error =  [
                            "message" => ("there is a holiday on date" . $request_data["in_date"]),
                     ];
                        throw new Exception(json_encode($error),400);
                    }

                }

                $start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                $end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                $capacity_hours = $end_at->diffInHours($start_at);


                $in_time = Carbon::createFromFormat('H:i:s', $request_data["in_time"]);
                $out_time = Carbon::createFromFormat('H:i:s', $request_data["out_time"]);
                $total_worked_hours = $out_time->diffInHours($in_time);


                $work_hours_delta = $total_worked_hours - $capacity_hours;

                $total_paid_hours = $total_worked_hours;




                if($request_data["does_break_taken"]) {
                    if($work_shift->break_type == 'unpaid') {
                        $total_paid_hours -= $work_shift->break_hours;

                    }
                }

                if($work_hours_delta > 0){
                    $regular_work_hours =  $total_paid_hours - $work_hours_delta;
                  } else {
                     $regular_work_hours = $total_paid_hours;
                  }



                $request_data["capacity_hours"] = $capacity_hours;
                $request_data["work_hours_delta"] = $work_hours_delta;
                $request_data["break_type"] = $work_shift->break_type;
                $request_data["break_hours"] = $work_shift->break_hours;
                $request_data["total_paid_hours"] = $total_paid_hours;
                $request_data["regular_work_hours"] = $regular_work_hours;


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
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             return DB::transaction(function () use ($request) {
                 if (!$request->user()->hasPermissionTo('attendance_create')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }

                 $request_data = $request->validated();

                 $work_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                    $query->where('users.id', $request_data["employee_id"]);
                })->first();
                if (!$work_shift) {
                    return response()->json(["message" => "Please define workshift first"], 400);
                }




                 $attendances_data = collect($request_data["attendance_details"])->map(function ($item) use($request_data, $work_shift) {
                    $day_number = Carbon::parse($item["in_date"])->dayOfWeek;
                    $work_shift_details =  $work_shift->details()->where([
                        "day" => $day_number
                    ])
                        ->first();

                    if (!$work_shift_details) {
                        $error =  [
                            "message" => ("No work shift details found  day" . $day_number),
                     ];
                        throw new Exception(json_encode($error),400);
                    }



                    if($work_shift_details->is_weekend){
                        $error =  [
                            "message" => ("there is a weekend on date" . $item["in_date"]),
                     ];
                        throw new Exception(json_encode($error),400);

                    }

                    $holiday =   Holiday::where([
                        "business_id" => auth()->user()->business_id
                    ])
                    ->where('holidays.start_date', "<=", $item["in_date"])
                    ->where('holidays.end_date', ">=", $item["in_date"] . ' 23:59:59')
                    ->first();

                    if($holiday){
                        if($holiday->is_active) {
                            $error =  [
                                "message" => ("there is a holiday on date" . $item["in_date"]),
                         ];
                            throw new Exception(json_encode($error),400);

                        }

                    }

                    $start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                    $end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                    $capacity_hours = $end_at->diffInHours($start_at);


                    $in_time = Carbon::createFromFormat('H:i:s', $item["in_time"]);
                    $out_time = Carbon::createFromFormat('H:i:s', $item["out_time"]);
                    $total_worked_hours = $out_time->diffInHours($in_time);


                    $work_hours_delta = $total_worked_hours - $capacity_hours;

                    $total_paid_hours = $total_worked_hours;




                    if($item["does_break_taken"]) {
                        if($work_shift->break_type == 'unpaid') {
                            $total_paid_hours -= $work_shift->break_hours;

                        }
                    }

                    if($work_hours_delta > 0){
                        $regular_work_hours =  $total_paid_hours - $work_hours_delta;
                      } else {
                         $regular_work_hours = $total_paid_hours;
                      }




                return [
                    "employee_id" => $request_data["employee_id"],
                    "business_id" => auth()->user()->business_id,
                    "is_active" => True,
                    "created_by" => auth()->user()->id,
                    "note" => $item["note"],
                    "in_time" => $item["in_time"],
                    "out_time" => $item["out_time"],
                    "in_date" => $item["in_date"],
                    "does_break_taken" => $item["does_break_taken"],
                    "capacity_hours" => $capacity_hours,
                    "work_hours_delta" => $work_hours_delta,
                    "break_type" => $work_shift->break_type,
                    "break_hours" => $work_shift->break_hours,
                    "total_paid_hours" => $total_paid_hours,
                    "regular_work_hours" => $regular_work_hours




                ];

            });


            $employee = User::where([
                "id" => $request_data["employee_id"]
            ])
            ->first();



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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
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
                    throw new Exception(json_encode($error),400);
                }
                if($work_shift_details->is_weekend){
                    $error =  [
                        "message" => ("there is a weekend on date" . $request_data["in_date"]),
                 ];
                    throw new Exception(json_encode($error),400);
                }
                $holiday =   Holiday::where([
                    "business_id" => auth()->user()->business_id
                ])
                ->where('holidays.start_date', "<=", $request_data["in_date"])
                ->where('holidays.end_date', ">=", $request_data["in_date"] . ' 23:59:59')
                ->first();

                if($holiday){
                    if($holiday->is_active) {
                        $error =  [
                            "message" => ("there is a holiday on date" . $request_data["in_date"]),
                     ];
                        throw new Exception(json_encode($error),400);
                    }

                }

                $start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                $end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                $capacity_hours = $end_at->diffInHours($start_at);


                $in_time = Carbon::createFromFormat('H:i:s', $request_data["in_time"]);
                $out_time = Carbon::createFromFormat('H:i:s', $request_data["out_time"]);
                $total_worked_hours = $out_time->diffInHours($in_time);


                $work_hours_delta = $total_worked_hours - $capacity_hours;

                $total_paid_hours = $total_worked_hours;




                if($request_data["does_break_taken"]) {
                    if($work_shift->break_type == 'unpaid') {
                        $total_paid_hours -= $work_shift->break_hours;

                    }
                }

                if($work_hours_delta > 0){
                  $regular_work_hours =  $total_paid_hours - $work_hours_delta;
                } else {
                   $regular_work_hours = $total_paid_hours;
                }



                $request_data["capacity_hours"] = $capacity_hours;
                $request_data["work_hours_delta"] = $work_hours_delta;
                $request_data["break_type"] = $work_shift->break_type;
                $request_data["break_hours"] = $work_shift->break_hours;
                $request_data["total_paid_hours"] = $total_paid_hours;
                $request_data["regular_work_hours"] = $regular_work_hours;
                $attendance  =  tap(Attendance::where($attendance_query_params))->update(
                    collect($request_data)->only([
        'note',
        'employee_id',
        'in_time',
        'out_time',
        'in_date',
        'does_break_taken',
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('attendance_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $attendances = Attendance::with([
                "employee" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
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
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('attendances.created_at', ">=", Carbon::createFromFormat('d-m-Y', ($request->start_date)));
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('attendances.created_at', "<=", Carbon::createFromFormat('d-m-Y', ($request->end_date . ' 23:59:59'))->format('Y-m-d'));
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
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


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
