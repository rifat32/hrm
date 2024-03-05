<?php

namespace App\Http\Controllers;

use App\Exports\LeavesExport;
use App\Http\Requests\LeaveApproveRequest;
use App\Http\Requests\LeaveBypassRequest;
use App\Http\Requests\LeaveCreateRequest;
use App\Http\Requests\LeaveUpdateRequest;
use App\Http\Requests\MultipleFileUploadRequest;
use App\Http\Utils\BasicNotificationUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\LeaveUtil;
use App\Http\Utils\PayrunUtil;
use App\Http\Utils\UserActivityUtil;
use App\Jobs\SendNotificationJob;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\LeaveHistory;
use App\Models\LeaveRecord;
use App\Models\PayrollAttendance;
use App\Models\Role;
use App\Models\SettingLeave;
use App\Models\User;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;


class LeaveController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, LeaveUtil, PayrunUtil, BasicNotificationUtil;

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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");

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
     *   @OA\Property(property="user_id", type="integer", format="int", example=2),
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");


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
                $request_data["status"] = (auth()->user()->hasRole("business_owner")?"approved" :"pending_approval");





                // $work_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                //     $query->where('users.id', $request_data["user_id"]);
                // })->first();

                // if (!$work_shift) {
                //     $this->storeError(
                //         "Please define workshift first"
                //         ,
                //         400,
                //         "front end error",
                //         "front end error"
                //        );
                //     return response()->json(["message" => "Please define workshift first"], 400);
                // }
                // if (!$work_shift->is_active) {
                //     $this->storeError(
                //         ("Please activate the work shift named '". $work_shift->name . "'")
                //         ,
                //         400,
                //         "front end error",
                //         "front end error"
                //        );
                //     return response()->json(["message" => ("Please activate the work shift named '". $work_shift->name . "'")], 400);
                // }
                if($request_data["leave_duration"] == "multiple_day") {
                    $work_shift_start_date = $request_data["start_date"];
                  } else {
                     $work_shift_start_date = $request_data["date"];
                  }
                $work_shift_history =  WorkShiftHistory::
                    where("from_date", "<", $work_shift_start_date)
                   ->where(function($query) use($work_shift_start_date){
                           $query->where("to_date",">=", $work_shift_start_date)
                           ->orWhereNull("to_date");
                   })
                   ->whereHas("users", function($query) use($work_shift_start_date,$request_data) {
                       $query->where("users.id", $request_data["user_id"])
                       ->where("employee_user_work_shift_histories.from_date", "<", $work_shift_start_date)
                       ->where(function($query) use($work_shift_start_date){
                               $query->where("employee_user_work_shift_histories.to_date",">=", $work_shift_start_date)
                               ->orWhereNull("employee_user_work_shift_histories.to_date");
                       });
                   })->first();


                   if (!$work_shift_history) {
                    $this->storeError(
                        "Please define workshift first"
                        ,
                        400,
                        "front end error",
                        "front end error"
                       );
                    return response()->json(["message" => "Please define workshift first"], 400);
                }

                $leave_record_data_list = [];
                $all_parent_department_ids = [];
$assigned_departments = Department::whereHas("users", function($query) use ($request_data) {
         $query->where("users.id",$request_data['user_id']);
})->get();


foreach ($assigned_departments as $assigned_department) {
    array_push($all_parent_department_ids,$assigned_department->id);
    $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
}

                if ($request_data["leave_duration"] == "single_day") {


                    $dateString = $request_data["date"];
                    $request_data["start_date"] = $request_data["date"];
                    $request_data["end_date"] = $request_data["date"];

                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift_history->details()->where([
                        "day" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        $this->storeError(
                            "No work shift details found"
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => "No work shift details found"], 400);
                    }

                    $holiday =   Holiday::where([
                        "business_id" => $request->user()->business_id
                    ])
                    ->where('holidays.start_date', "<=", $request_data["date"])
                    ->where('holidays.end_date', ">=", $request_data["date"] . ' 23:59:59')
                    ->where(function ($query) use ($request_data,$all_parent_department_ids) {
                        $query->whereHas("users", function ($query) use ($request_data) {
                            $query->where([
                                "users.id" => $request_data['user_id']
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

                    $previous_leave =  Leave::where([
                        "user_id" => $request_data["user_id"]
                    ])
                    ->whereHas('records', function ($query) use ($request_data) {
                        $query->where('leave_records.date',($request_data["date"]));
                    })->first();

                    // if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave)  || auth()->user()->hasRole("business_owner") ) {

                        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave)  ) {


                            $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                            $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                            $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                            $leave_record_data["leave_hours"] =  $capacity_hours;
                            $leave_record_data["capacity_hours"] =  $capacity_hours;
                        $leave_record_data["start_time"] = $work_shift_details->start_at;
                        $leave_record_data["end_time"] = $work_shift_details->end_at;
                        $leave_record_data["date"] = ($request_data["date"]);
                        array_push($leave_record_data_list, $leave_record_data);
                    }




                } else if ($request_data["leave_duration"] == "multiple_day") {

                    $start_date = Carbon::parse($request_data["start_date"]);
                    $end_date = Carbon::parse($request_data["end_date"]);


                    $leave_dates = [];
                    for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
                        $leave_dates[] = $date->format('Y-m-d');
                    }

                    foreach ($leave_dates as $leave_date) {
                        $dateString = $leave_date;
                        $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                        $work_shift_details =  $work_shift_history->details()->where([
                            "day" => $dayNumber
                        ])
                            ->first();
                        if (!$work_shift_details) {
                            $this->storeError(
                                "No work shift details found"
                                ,
                                400,
                                "front end error",
                                "front end error"
                               );
                            return response()->json(["message" => "No work shift details found"], 400);
                        }

                        $holiday =   Holiday::where([
                            "business_id" => $request->user()->business_id
                        ])
                        ->where('holidays.start_date', "<=", $leave_date)
                        ->where('holidays.end_date', ">=", $leave_date)
                        ->where(function ($query) use ($request_data,$all_parent_department_ids) {
                            $query->whereHas("users", function ($query) use ($request_data) {
                                $query->where([
                                    "users.id" => $request_data['user_id']
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


                        $previous_leave =  Leave::where([
                            "user_id" => $request_data["user_id"]
                        ])
                        ->whereHas('records', function ($query) use ($leave_date) {
                            $query->where('leave_records.date', $leave_date);
                        })->first();


                    //    if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave) || auth()->user()->hasRole("business_owner") ) {

                        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave)) {


                            $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                            $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                            $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                            $leave_record_data["leave_hours"] =  $capacity_hours;
                            $leave_record_data["capacity_hours"] =  $capacity_hours;


                            $leave_record_data["start_time"] = $work_shift_details->start_at;
                            $leave_record_data["end_time"] = $work_shift_details->end_at;
                            $leave_record_data["date"] = $leave_date;
                            array_push($leave_record_data_list, $leave_record_data);
                        }
                    }
                } else if ($request_data["leave_duration"] == "half_day") {

                    $dateString = $request_data["date"];
                    $request_data["start_date"] = $request_data["date"];
                    $request_data["end_date"] = $request_data["date"];
                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift_history->details()->where([
                        "day" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        $this->storeError(
                            "No work shift details found"
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => "No work shift details found"], 400);
                    }
                    $holiday =   Holiday::where([
                        "business_id" => $request->user()->business_id
                    ])
                   ->where('holidays.start_date', "<=", $request_data["date"])
                   ->where('holidays.end_date', ">=", ($request_data["date"] . ' 23:59:59'))
                   ->where(function ($query) use ($request_data,$all_parent_department_ids) {
                    $query->whereHas("users", function ($query) use ($request_data) {
                        $query->where([
                            "users.id" => $request_data['user_id']
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

                    $previous_leave =  Leave::where([
                        "user_id" => $request_data["user_id"]
                    ])
                    ->whereHas('records', function ($query) use ($request_data) {
                        $query->where('leave_records.date', $request_data["date"]);
                    })->first();


                    // if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave) || auth()->user()->hasRole("business_owner") ) {


                        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave) ) {

                            $leave_start_at = Carbon::parse($work_shift_details->start_at);
                            $leave_end_at = Carbon::parse($work_shift_details->end_at);

                            if ($request_data["day_type"] == "first_half") {
                                // Create clones of $leave_start_at and $leave_end_at
                                $temp_start_at = clone $leave_start_at;
                                $temp_end_at = clone $leave_end_at;

                                // Set $leave_end_at to be the middle time
                                $leave_end_at = $temp_start_at->addMinutes($temp_start_at->diffInMinutes($temp_end_at) / 2);
                            } elseif ($request_data["day_type"] == "last_half") {
                                // Create clones of $leave_start_at and $leave_end_at
                                $temp_start_at = clone $leave_start_at;
                                $temp_end_at = clone $leave_end_at;

                                // Set $leave_start_at to be the middle time
                                $leave_start_at = $temp_start_at->addMinutes($temp_start_at->diffInMinutes($temp_end_at) / 2);
                            }


                        $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                        $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                        $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                        $leave_record_data["capacity_hours"] =  $capacity_hours;



                        $leave_hours = $leave_end_at->diffInHours($leave_start_at);
                        $leave_record_data["leave_hours"] =  $leave_hours;






                        $leave_record_data["start_time"] = $leave_start_at;
                        $leave_record_data["end_time"] = $leave_end_at;
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                } else if ($request_data["leave_duration"] == "hours") {

                    $dateString = $request_data["date"];
                    $request_data["start_date"] = $request_data["date"];
                    $request_data["end_date"] = $request_data["date"];

                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift_history->details()->where([
                        "day" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        $this->storeError(
                            "No work shift details found"
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => "No work shift details found"], 400);
                    }
                    $start_time = Carbon::parse($request_data["start_time"]);
                    $work_shift_start = Carbon::parse($work_shift_details->start_at);
                    if ($start_time->lessThan($work_shift_start)) {
                        $this->storeError(
                            ("The employee does not start working at " . $request_data["start_time"])
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => ("The employee does not start working at " . $request_data["start_time"] . ". He starts at " . $work_shift_details->start_at)], 400);
                    }
                    $end_time = Carbon::parse($request_data["end_time"]);
                    $work_shift_end = Carbon::parse($work_shift_details->end_at);
                    if ($end_time->greaterThan($work_shift_end)) {
                        $this->storeError(
                            ("The employee does not close working at " . $request_data["end_time"])
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => ("The employee does not close working at " . $request_data["end_time"])], 400);
                    }

                    $holiday =   Holiday::where([
                        "business_id" => $request->user()->business_id
                    ])

                    ->where('holidays.start_date', "<=", $request_data["date"])

                    ->where('holidays.end_date', ">=", ($request_data["date"] . ' 23:59:59'))
                    ->where(function ($query) use ($request_data,$all_parent_department_ids) {
                        $query->whereHas("users", function ($query) use ($request_data) {
                            $query->where([
                                "users.id" => $request_data['user_id']
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

                    $previous_leave =  Leave::where([
                        "user_id" => $request_data["user_id"]
                    ])
                    ->whereHas('records', function ($query) use ($request_data) {
                        $query->where('leave_records.date', $request_data["date"]);
                    })->first();


                    // if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave) || auth()->user()->hasRole("business_owner")) {

                        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave) ) {

                            $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                            $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                            $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                            $leave_record_data["capacity_hours"] =  $capacity_hours;

                            $leave_start_at = Carbon::createFromFormat('H:i:s', $request_data["start_time"]);
                            $leave_end_at = Carbon::createFromFormat('H:i:s', $request_data["end_time"]);
                            $leave_hours = $leave_end_at->diffInHours($leave_start_at);
                            $leave_record_data["leave_hours"] =  $leave_hours;



                        $leave_record_data["start_time"] = $request_data["start_time"];
                        $leave_record_data["end_time"] = $request_data["end_time"];
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                }
                // foreach($leave_record_data_list as $leave_record_data) {

                //     // $holiday =   Holiday::where([
                //     //     "business_id" => $request->user()->business_id
                //     // ])
                //     // ->where('holidays.start_date', "<=", $leave_record_data["date"])
                //     // ->where('holidays.end_date', ">=", $leave_record_data["date"] . ' 23:59:59')
                //     // ->first();
                //     // if ($holiday) {
                //     //     if($holiday->is_active){
                //     //         return response()->json(["message" => ("There is a holiday on " . $leave_record_data["date"])], 400);
                //     //         // $leave_date = Carbon::parse($leave_record_data["date"]);
                //     //         // $holiday_created_at = Carbon::parse($holiday->created_at);
                //     //         // if (!$holiday->repeats_annually && !($leave_date->diffInYears($holiday_created_at) > 1)) {
                //     //         //     return response()->json(["message" => ("There is a holiday on " . $leave_record_data["date"])], 400);
                //     //         // }
                //     //         // return response()->json(["message" => ("There is a holiday on " . $leave_record_data["date"])], 400);

                //     //     }

                //     // }


                // $previous_leave =  Leave::where([
                //     "user_id" => $request->user()->id
                // ])
                // ->whereHas('records', function ($query) use ($leave_record_data) {
                //     $query->where('leave_records.date', $leave_record_data["date"]);
                // })->first();
                // if ($previous_leave) {
                //     return response()->json(["message" => "Leave already exists for the employee on " . $leave_record_data["date"]], 400);
                // }

                // }



                $leave =  Leave::create($request_data);
                $leave_records =   $leave->records()->createMany($leave_record_data_list);




                $leave_history_data = $leave->toArray();
                $leave_history_data['leave_id'] = $leave->id;
                $leave_history_data['actor_id'] = auth()->user()->id;
                $leave_history_data['action'] = "create";
                $leave_history_data['is_approved'] = NULL;
                $leave_history_data['leave_created_at'] = $leave->created_at;
                $leave_history_data['leave_updated_at'] = $leave->updated_at;


                // $leave_history = LeaveHistory::create($leave_history_data);
                // $leave_record_history = $leave_records->toArray();
                // $leave_record_history["leave_id"] = $leave_history->id;
                // $leave_history->records()->createMany($leave_record_history);





                $this->send_notification($leave, $leave->employee, "Leave Request Taken", "create", "leave");

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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
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





                $process_leave_approval =   $this->processLeaveApproval($request_data["leave_id"],$request_data["is_approved"]);
                if (!$process_leave_approval["success"]) {

                    $this->storeError(
                        $process_leave_approval["message"]
                        ,
                        $process_leave_approval["status"]
                        ,
                        "front end error",
                        "front end error"
                       );
                    return response([
                        "message" => $process_leave_approval["message"]
                    ], $process_leave_approval["status"]);
                }



                $leave = Leave::where([
                    "id" => $request_data["leave_id"]
                ])->first();

                $leave_history_data = $leave->toArray();
                $leave_history_data['leave_id'] = $leave->id;
                $leave_history_data['actor_id'] = auth()->user()->id;
                $leave_history_data['action'] = "approve";
                $leave_history_data['is_approved'] =  $request_data['is_approved'];
                $leave_history_data['leave_created_at'] = $leave->created_at;
                $leave_history_data['leave_updated_at'] = $leave->updated_at;
                $leave_history = LeaveHistory::create($leave_history_data);


                foreach($leave->records as $leave_record){
                    $this->adjust_payroll_on_leave_update($leave_record);

                    $attendance = Attendance::where([
                              "leave_record_id" => $leave_record->id
                          ])

                     ->first();

                     $other_attendance =   Attendance::where([
                        "user_id" => $leave->user_id
                    ])
                    ->where("in_date", "=", $leave_record->date)
                    ->whereNotIn("leave_record_id",[$leave_record->id])
                    ->first();
                    if(!$attendance && !$other_attendance) {
                        continue;
                    }
                    if($attendance) {
                        if($attendance->leave_record_id) {
                            $leave_record_date = Carbon::parse($leave_record->date);
                            $attendance_date = Carbon::parse($attendance->in_date);
                            if($leave_record_date->isSameDay($attendance_date)) {

                            $this->update_attendance_accordingly($attendance,$leave_record);

                            } else {
                                $attendance->update([
                                    "leave_start_time" => NULL,
                                    "leave_end_time" => NULL,
                                    "leave_record_id" => NULL,
                                    "leave_hours" => 0
                                    ]);
                            }
                        }
                    }
                    if($other_attendance) {

                          $other_attendance = Attendance::find($other_attendance->id);

                          $other_attendance->update([
                            "leave_start_time" => $leave_record->start_time,
                            "leave_end_time" => $leave_record->leave_end_time,
                            "leave_record_id" => $leave_record->id,
                            "leave_hours" => $leave_record->leave_hours,
                            ]);

                          $this->update_attendance_accordingly($other_attendance,$leave_record);
                    }
                //   call generate payrun
                if($attendance) {
                    $this->recalculate_payroll($attendance);
                }

                if ($other_attendance) {
                    $this->recalculate_payroll($other_attendance);
                }
                      }



                      if($request_data["is_approved"]) {
                        $this->send_notification($leave, $leave->employee, "Leave Request Approved", "approve", "leave");
                      } else {
                        $this->send_notification($leave, $leave->employee, "Leave Request Rejected", "reject", "leave");
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
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

                if (!$setting_leave->allow_bypass) {
                    $this->storeError(
                        "bypass not allowed"
                        ,
                        400,
                        "front end error",
                        "front end error"
                       );
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
                    $this->storeError(
                        "no leave found"
                        ,
                        400,
                        "front end error",
                        "front end error"
                       );
                    return response([
                        "message" => "no leave found"
                    ], 400);
                }
                $leave->status = "approved";
                $leave->save();




                $leave_history_data = $leave->toArray();
                $leave_history_data['leave_id'] = $leave->id;
                $leave_history_data['actor_id'] = auth()->user()->id;
                $leave_history_data['action'] = "bypass";
                $leave_history_data['is_approved'] = NULL;
                $leave_history_data['leave_created_at'] = $leave->created_at;
                $leave_history_data['leave_updated_at'] = $leave->updated_at;


                $this->send_notification($leave, $leave->employee, "Leave Request Approved", "approve", "leave");

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
     *   @OA\Property(property="user_id", type="integer", format="int", example=2),
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('leave_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();


                if($request_data["leave_duration"] == "multiple_day") {
                  $work_shift_start_date = $request_data["start_date"];
                } else {
                   $work_shift_start_date = $request_data["date"];
                }

                $work_shift_history =  WorkShiftHistory::
                    where("from_date", "<", $work_shift_start_date)
                   ->where(function($query) use($work_shift_start_date){
                           $query->where("to_date",">=", $work_shift_start_date)
                           ->orWhereNull("to_date");
                   })
                   ->whereHas("users", function($query) use($work_shift_start_date,$request_data) {
                       $query->where("users.id", $request_data["user_id"])
                       ->where("employee_user_work_shift_histories.from_date", "<", $work_shift_start_date)
                       ->where(function($query) use($work_shift_start_date){
                               $query->where("employee_user_work_shift_histories.to_date",">=", $work_shift_start_date)
                               ->orWhereNull("employee_user_work_shift_histories.to_date");
                       });
                   })->first();

                   if (!$work_shift_history) {
                    $this->storeError(
                        "Please define workshift first"
                        ,
                        400,
                        "front end error",
                        "front end error"
                       );
                    return response()->json(["message" => "Please define workshift first"], 400);
                }


                // $work_shift =   WorkShift::whereHas('users', function ($query) use ($request_data) {
                //     $query->where('users.id', $request_data["user_id"]);
                // })->first();

                // if (!$work_shift) {
                //     $this->storeError(
                //         "Please define workshift first"
                //         ,
                //         400,
                //         "front end error",
                //         "front end error"
                //        );
                //     return response()->json(["message" => "Please define workshift first"], 400);
                // }
                // if (!$work_shift->is_active) {
                //     $this->storeError(
                //         ("Please activate the work shift named '". $work_shift->name . "'")
                //         ,
                //         400,
                //         "front end error",
                //         "front end error"
                //        );
                //     return response()->json(["message" => ("Please activate the work shift named '". $work_shift->name . "'")], 400);
                // }
                // if (!$wors_shift) {
                //     $department = Department::whereHas('users', function ($query) use ($request_data) {
                //         $query->where('id', $request_data["user_id"]);
                //     })->first();

                //     if (!$department) {
                //         return response()->json(["message" => "Hey please specify department for the employee first!"], 400);
                //     }

                //     $all_department_ids = $department->all_parent_ids;

                //     $work_shift = WorkShift::whereHas('departments', function ($query) use ($all_department_ids) {
                //         $query->whereIn('id', $all_department_ids);
                //     })->orderByRaw('FIELD(department_id, ' . implode(',', $all_department_ids) . ')')->first();
                //     if (!$work_shift) {
                //         return response()->json(["message" => "Please define workshift first"], 400);
                //     }
                // }

                $all_parent_department_ids = [];
                $assigned_departments = Department::whereHas("users", function($query) use ($request_data) {
                         $query->where("users.id",$request_data['user_id']);
                })->get();


                foreach ($assigned_departments as $assigned_department) {
                    array_push($all_parent_department_ids,$assigned_department->id);
                    $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
                }

                $leave_record_data_list = [];
                if ($request_data["leave_duration"] == "single_day") {
                    $dateString = $request_data["date"];
                    $request_data["start_date"] = $request_data["date"];
                    $request_data["end_date"] = $request_data["date"];
                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift_history->details()->where([
                        "day" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        $this->storeError(
                            "No work shift details found"
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => "No work shift details found"], 400);
                    }

                    $holiday =   Holiday::where([
                        "business_id" => $request->user()->business_id
                    ])
                    ->where('holidays.start_date', "<=", $request_data["date"])
                    ->where('holidays.end_date', ">=", ($request_data["date"] . ' 23:59:59'))
                    ->where(function ($query) use ($request_data,$all_parent_department_ids) {
                        $query->whereHas("users", function ($query) use ($request_data) {
                            $query->where([
                                "users.id" => $request_data['user_id']
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

                    $previous_leave =  Leave::where([
                        "user_id" => $request_data["user_id"]
                    ])
                    ->whereNotIn("id",[$request_data["id"]])
                    ->whereHas('records', function ($query) use ($request_data) {
                        $query->where('leave_records.date', $request_data["date"]);
                    })->first();


                        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave) ) {

                            $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                            $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                            $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                            $leave_record_data["leave_hours"] =  $capacity_hours;
                            $leave_record_data["capacity_hours"] =  $capacity_hours;



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
                        $leave_dates[] = $date->format('Y-m-d');
                    }
                    foreach ($leave_dates as $leave_date) {
                        $dateString = $leave_date;
                        $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                        $work_shift_details =  $work_shift_history->details()->where([
                            "day" => $dayNumber
                        ])
                            ->first();
                        if (!$work_shift_details) {
                            $this->storeError(
                                "No work shift details found"
                                ,
                                400,
                                "front end error",
                                "front end error"
                               );
                            return response()->json(["message" => "No work shift details found"], 400);
                        }
                        $holiday =   Holiday::where([
                            "business_id" => $request->user()->business_id
                        ])
                        ->where('holidays.start_date', "<=", $leave_date)
                        ->where('holidays.end_date', ">=", $leave_date)
                        ->where(function ($query) use ($request_data,$all_parent_department_ids) {
                            $query->whereHas("users", function ($query) use ($request_data) {
                                $query->where([
                                    "users.id" => $request_data['user_id']
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

                        $previous_leave =  Leave::where([
                            "user_id" => $request_data["user_id"]
                        ])
                        ->whereNotIn("id",[$request_data["id"]])
                        ->whereHas('records', function ($query) use ($leave_date) {
                            $query->where('leave_records.date', $leave_date);
                        })->first();



                            if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave) ) {



                                $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                                $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                                $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                                $leave_record_data["leave_hours"] =  $capacity_hours;
                                $leave_record_data["capacity_hours"] =  $capacity_hours;


                            $leave_record_data["start_time"] = $work_shift_details->start_at;
                            $leave_record_data["end_time"] = $work_shift_details->end_at;
                            $leave_record_data["date"] = $leave_date;
                            array_push($leave_record_data_list, $leave_record_data);
                        }
                    }
                } else if ($request_data["leave_duration"] == "half_day") {

                    $dateString = $request_data["date"];
                    $request_data["start_date"] = $request_data["date"];
                    $request_data["end_date"] = $request_data["date"];

                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift_history->details()->where([
                        "day" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        $this->storeError(
                            "No work shift details found"
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => "No work shift details found"], 400);
                    }
                    $holiday =   Holiday::where([
                        "business_id" => $request->user()->business_id
                    ])
                    ->where('holidays.start_date', "<=",$request_data["date"])
                    ->where('holidays.end_date', ">=", ($request_data["date"] . ' 23:59:59'))
                    ->where(function ($query) use ($request_data,$all_parent_department_ids) {
                        $query->whereHas("users", function ($query) use ($request_data) {
                            $query->where([
                                "users.id" => $request_data['user_id']
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

                    $previous_leave =  Leave::where([
                        "user_id" => $request_data["user_id"]
                    ])
                    ->whereNotIn("id",[$request_data["id"]])
                    ->whereHas('records', function ($query) use ($request_data) {
                        $query->where('leave_records.date', $request_data["date"]);
                    })->first();




                        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave)) {


                            $leave_start_at = Carbon::parse($work_shift_details->start_at);
                            $leave_end_at = Carbon::parse($work_shift_details->end_at);

                            if ($request_data["day_type"] == "first_half") {
                                // Create clones of $leave_start_at and $leave_end_at
                                $temp_start_at = clone $leave_start_at;
                                $temp_end_at = clone $leave_end_at;

                                // Set $leave_end_at to be the middle time
                                $leave_end_at = $temp_start_at->addMinutes($temp_start_at->diffInMinutes($temp_end_at) / 2);
                            } elseif ($request_data["day_type"] == "last_half") {
                                // Create clones of $leave_start_at and $leave_end_at
                                $temp_start_at = clone $leave_start_at;
                                $temp_end_at = clone $leave_end_at;

                                // Set $leave_start_at to be the middle time
                                $leave_start_at = $temp_start_at->addMinutes($temp_start_at->diffInMinutes($temp_end_at) / 2);
                            }


                        $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                        $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                        $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                        $leave_record_data["capacity_hours"] =  $capacity_hours;



                        $leave_hours = $leave_end_at->diffInHours($leave_start_at);
                        $leave_record_data["leave_hours"] =  $leave_hours;






                        $leave_record_data["start_time"] = $leave_start_at;
                        $leave_record_data["end_time"] = $leave_end_at;
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                } else if ($request_data["leave_duration"] == "hours") {

                    $dateString = $request_data["date"];
                    $request_data["start_date"] = $request_data["date"];
                    $request_data["end_date"] = $request_data["date"];
                    $dayNumber = Carbon::parse($dateString)->dayOfWeek;
                    $work_shift_details =  $work_shift_history->details()->where([
                        "day" => $dayNumber
                    ])
                        ->first();
                    if (!$work_shift_details) {
                        $this->storeError(
                            "No work shift details found"
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => "No work shift details found"], 400);
                    }
                    $start_time = Carbon::parse($request_data["start_time"]);
                    $work_shift_start = Carbon::parse($work_shift_details->start_at);
                    if ($start_time->lessThan($work_shift_start)) {
                        $this->storeError(
                            ("The employee does not start working at " . $request_data["start_time"])
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => ("The employee does not start working at " . $request_data["start_time"] . ". He starts at " . $work_shift_details->start_at)], 400);
                    }
                    $end_time = Carbon::parse($request_data["end_time"]);
                    $work_shift_end = Carbon::parse($work_shift_details->end_at);
                    if ($end_time->greaterThan($work_shift_end)) {
                        $this->storeError(
                            ("The employee does not close working at " . $request_data["end_time"])
                            ,
                            400,
                            "front end error",
                            "front end error"
                           );
                        return response()->json(["message" => ("The employee does not close working at " . $request_data["end_time"])], 400);
                    }


                    $holiday =   Holiday::where([
                        "business_id" => $request->user()->business_id
                    ])
                    ->where('holidays.start_date', "<=", $request_data["date"])
                    ->where('holidays.end_date', ">=", ($request_data["date"] . ' 23:59:59'))
                    ->where(function ($query) use ($request_data,$all_parent_department_ids) {
                        $query->whereHas("users", function ($query) use ($request_data) {
                            $query->where([
                                "users.id" => $request_data['user_id']
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

                    $previous_leave =  Leave::where([
                        "user_id" => $request_data["user_id"]
                    ])
                    ->whereNotIn("id",[$request_data["id"]])
                    ->whereHas('records', function ($query) use ($request_data) {
                        $query->where('leave_records.date', $request_data["date"]);
                    })->first();



                        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave)) {

                            $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
                            $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
                            $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                            $leave_record_data["capacity_hours"] =  $capacity_hours;

                            $leave_start_at = Carbon::createFromFormat('H:i:s', $request_data["start_time"]);
                            $leave_end_at = Carbon::createFromFormat('H:i:s', $request_data["end_time"]);
                            $leave_hours = $leave_end_at->diffInHours($leave_start_at);
                            $leave_record_data["leave_hours"] =  $leave_hours;

                        $leave_record_data["start_time"] = $request_data["start_time"];
                        $leave_record_data["end_time"] = $request_data["end_time"];
                        $leave_record_data["date"] = $request_data["date"];
                        array_push($leave_record_data_list, $leave_record_data);
                    }
                }

                // foreach($leave_record_data_list as $leave_record_data) {
                //     // $holiday =   Holiday::where([
                //     //     "business_id" => $request->user()->business_id
                //     // ])
                //     // ->where('holidays.start_date', "<=", $leave_record_data["date"])
                //     // ->where('holidays.end_date', ">=", $leave_record_data["date"] . ' 23:59:59')
                //     // ->first();
                //     // if ($holiday) {
                //     //     if($holiday->is_active){
                //     //         return response()->json(["message" => ("There is a holiday on " . $leave_record_data["date"])], 400);
                //     //         // $leave_date = Carbon::parse($leave_record_data["date"]);
                //     //         // $holiday_created_at = Carbon::parse($holiday->created_at);
                //     //         // if (!$holiday->repeats_annually && !($leave_date->diffInYears($holiday_created_at) > 1)) {
                //     //         //     return response()->json(["message" => ("There is a holiday on " . $leave_record_data["date"])], 400);
                //     //         // }
                //     //         // return response()->json(["message" => ("There is a holiday on " . $leave_record_data["date"])], 400);

                //     //     }

                //     // }


                // // $previous_leave =  Leave::where([
                // //     "user_id" => $request_data["user_id"]
                // // ])
                // // ->whereNotIn("id",[$request_data["id"]])
                // // ->whereHas('records', function ($query) use ($leave_record_data) {
                // //     $query->where('leave_records.date', $leave_record_data["date"]);
                // // })->first();
                // // if ($previous_leave) {
                // //     return response()->json(["message" => "Leave already exists for the employee on " . $leave_record_data["date"]], 400);
                // // }

                // }

                $leave_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $leave  =  tap(Leave::where($leave_query_params))->update(
                    collect($request_data)->only([
                        'leave_duration',
                        'day_type',
                        'leave_type_id',
                        'user_id',
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


                $leave_records = $leave->records()->createMany($leave_record_data_list);

                $leave_history_data = $leave->toArray();
                $leave_history_data['leave_id'] = $leave->id;
                $leave_history_data['actor_id'] = auth()->user()->id;
                $leave_history_data['action'] = "update";
                $leave_history_data['is_approved'] = NULL;
                $leave_history_data['leave_created_at'] = $leave->created_at;
                $leave_history_data['leave_updated_at'] = $leave->updated_at;
                $leave_history = LeaveHistory::create($leave_history_data);

                $leave_record_history = $leave_records->toArray();
                $leave_record_history["leave_id"] = $leave_history->id;
                $leave_history->records()->createMany($leave_record_data_list);


                foreach($leave->records as $leave_record){


              $attendance = Attendance::where([
                        "leave_record_id" => $leave_record->id
                    ])

               ->first();

               if(!$attendance) {
                    continue;
               }

               $overtime_start_time = NULL;
               $overtime_end_time = NULL;
               $result_balance_hours = 0;
               $leave_hours = 0;

                if ($attendance->is_weekend || $attendance->holiday_id) {
                    $overtime_start_time = $attendance->in_time;
                    $overtime_end_time = $attendance->out_time;
                    $result_balance_hours = $attendance->total_paid_hours;

                } else if ($attendance->leave_record_id) {
                    $attendance_in_time = $attendance->in_time;
                    $attendance_out_time = $attendance->out_time;

                    $leave_start_time = Carbon::parse($leave_record->start_time);
                    $leave_end_time = Carbon::parse($leave_record->end_time);

                    $balance_start_time = $attendance_in_time->max($leave_start_time);
                    $balance_end_time = $attendance_out_time->min($leave_end_time);

                    // Check if there is any overlap
                    if ($balance_start_time < $balance_end_time) {
                        $overtime_start_time = $attendance->in_time;
                        $overtime_end_time = $attendance->out_time;
                        $result_balance_hours = $balance_start_time->diffInHours($balance_end_time);


                        $uncommon_attendance_start = $attendance_in_time->min($balance_start_time);
                        $uncommon_attendance_end = $attendance_out_time->max($balance_end_time);
                        $uncommon_leave_start = $leave_start_time->min($balance_start_time);
                        $uncommon_leave_end = $leave_end_time->max($balance_end_time);

                    } else {
                        $uncommon_attendance_start = $attendance_in_time;
                        $uncommon_attendance_end = $attendance_out_time;

                        $uncommon_leave_start = $leave_start_time;
                        $uncommon_leave_end = $leave_end_time;
                    }

                    $uncommon_attendance_hours = $uncommon_attendance_start->diffInHours($uncommon_attendance_end);
                    $uncommon_leave_hours = $uncommon_leave_start->diffInHours($uncommon_leave_end);

                    $leave_hours = $attendance->capacity_hours - ($uncommon_attendance_hours + $uncommon_leave_hours + $result_balance_hours);
                } else if ($attendance->work_hours_delta > 0) {
                    $result_balance_hours = $attendance->work_hours_delta;
                }  else if ($attendance->work_hours_delta < 0) {
                    $leave_hours = abs($attendance->work_hours_delta);
                }

             $regular_work_hours =  $attendance->total_paid_hours - $result_balance_hours;






               $attendance->update([
                "regular_work_hours" => $regular_work_hours,
                "overtime_start_time" => $overtime_start_time,
                "overtime_end_time" => $overtime_end_time,
                "overtime_hours" => $result_balance_hours,
                "leave_start_time" => $leave_record->start_time,
                "leave_end_time" => $leave_record->end_time,
               ]);

               $adjust_payroll_on_attendance_update = $this->adjust_payroll_on_attendance_update($attendance,$leave_record) ;
               if(!$adjust_payroll_on_attendance_update) {
                   $this->storeError([], 422, "leave update", "leave controller");
                   throw new Exception("some thing went wrong");
               }

                }




                $this->send_notification($leave, $leave->employee, "Leave Request Updated", "update", "leave");

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
     *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     * @OA\Parameter(
     * name="department_id",
     * in="query",
     * description="department_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="leave_type_id",
     * in="query",
     * description="leave_type_id",
     * required=true,
     * example="1"
     * ),
     *
     *
     * @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     * * @OA\Parameter(
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
 *  * @OA\Parameter(
 *     name="status",
 *     in="query",
 *     description="status",
 *     required=true,
 *     example="status"
 * ),
 * @OA\Parameter(
 *     name="total_leave_hours",
 *     in="query",
 *     description="Total Leave Hours",
 *     required=true,
 *     example="8"
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('leave_view')) {
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
            $leaves = Leave::where(
                [
                    "leaves.business_id" => $business_id
                ]
            )
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
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
                    return $query->where('leaves.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("employee", function ($query){
                        $query->whereNotIn("users.id",[auth()->user()->id]);
                    });
                })
                ->when(!empty($request->leave_type_id), function ($query) use ($request) {
                    return $query->where('leaves.leave_type_id', $request->leave_type_id);
                })
                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('leaves.status', $request->status);
                })


                ->when(!empty($request->department_id), function ($query) use ($request) {
                    return $query->whereHas("employee.departments", function($query) use($request) {
                        $query->where("departments.id",$request->department_id);
                     });
                })



                ->when(!empty($request->start_date), function ($query) use ($request) {
                    $query->where('leaves.start_date', '>=', $request->start_date . ' 00:00:00');
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    $query->where('leaves.end_date', '<=', $request->end_date . ' 23:59:59');
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
                });

                foreach ($leaves as $leave) {
                    $leave->total_leave_hours = $leave->records->sum(function ($record) {
                     $startTime = Carbon::parse($record->start_time);
                     $endTime = Carbon::parse($record->end_time);
                     return $startTime->diffInHours($endTime);

                    });

                 }

                 if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                    if (strtoupper($request->response_type) == 'PDF') {
                        $pdf = PDF::loadView('pdf.leaves', ["leaves" => $leaves]);
                        return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                    } elseif (strtoupper($request->response_type) === 'CSV') {

                        return Excel::download(new LeavesExport($leaves), ((!empty($request->file_name) ? $request->file_name : 'leave') . '.csv'));
                    }
                } else {
                    return response()->json($leaves, 200);
                }



        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v2.0/leaves",
     *      operationId="getLeavesV2",
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
     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *  *  * @OA\Parameter(
 *     name="status",
 *     in="query",
 *     description="status",
 *     required=true,
 *     example="status"
 * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *

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

    public function getLeavesV2(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('leave_view')) {
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
            $leaves = Leave::with([
                "employee" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name',
                        'users.image'
                    );
                },
                "employee.departments" => function ($query) {
                    // You can select specific fields from the departments table if needed
                    $query->select(
                        'departments.id',
                        'departments.name',
                        "departments.description"
                    );
                },
                "leave_type" => function ($query) {
                    $query->select(
                        'setting_leave_types.id',
                        'setting_leave_types.name',
                        'setting_leave_types.type',
                        'setting_leave_types.amount',

                    );
                },

            ])
                ->where(
                    [
                        "leaves.business_id" => $business_id
                    ]
                )
                ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        // $query->where("leaves.name", "like", "%" . $term . "%")
                        //     ->orWhere("leaves.description", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('leaves.status', $request->status);
                })
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('leaves.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("employee", function ($query){
                        $query->whereNotIn("users.id",[auth()->user()->id]);
                    });
                })

                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    $query->where('leaves.start_date', '>=', $request->start_date . ' 00:00:00');

                })
                ->when(!empty($request->end_date), function ($query) use ($request) {

                    $query->where('leaves.end_date', '<=', $request->end_date . ' 23:59:59');
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
                });

                foreach ($leaves as $leave) {
                   $leave->total_leave_hours = $leave->records->sum(function ($record) {
                    $startTime = Carbon::parse($record->start_time);
                    $endTime = Carbon::parse($record->end_time);
                    return $startTime->diffInHours($endTime);

                   });

                }
            $data["data"] = $leaves;


            $data["data_highlights"] = [];

            $data["data_highlights"]["employees_on_leave"] = $leaves->count();

            $data["data_highlights"]["total_leave_hours"] = $leaves->reduce(function ($carry, $leave) {
                return $carry + $leave->records->sum(function ($record) {
                    $startTime = \Carbon\Carbon::parse($record->start_time);
                    $endTime = \Carbon\Carbon::parse($record->end_time);

                    return $startTime->diffInHours($endTime);
                });
            }, 0);

            $data["data_highlights"]["single_day_leaves"] = $leaves->filter(function ($leave) {
                return $leave->leave_duration == "single_day";
            })->count();

            $data["data_highlights"]["multiple_day_leaves"] = $leaves->filter(function ($leave) {
                return $leave->leave_duration == "multiple_day";
            })->count();


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v3.0/leaves",
     *      operationId="getLeavesV3",
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
     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *  *  * @OA\Parameter(
 *     name="status",
 *     in="query",
 *     description="status",
 *     required=true,
 *     example="status"
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

    public function getLeavesV3(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");

            if (!$request->user()->hasPermissionTo('leave_view')) {
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
                    'leaves' => function ($query) use ($request) {
                $query->when(!empty($request->start_date), function ($query) use ($request) {
                        return $query->where('start_date', '>=', ($request->start_date . ' 00:00:00'));
                    })
                    ->when(!empty($request->end_date), function ($query) use ($request) {
                        return $query->where('end_date', '<=', ($request->end_date . ' 23:59:59'));
                    });
            },
            'departments' => function ($query) use ($request) {
                $query->select("departments.name");
            },




            ])
            ->whereHas("departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
            ->whereHas("leaves", function($q) use ($request)  {
                $q->whereNotNull("user_id")
                  ->when(!empty($request->user_id), function ($q) use ($request) {
                      $q->where('user_id', $request->user_id);
                  })
                  ->when(!empty($request->start_date), function ($q) use ($request) {
                      $q->where('start_date', '>=', $request->start_date . ' 00:00:00');
                  })
                  ->when(!empty($request->end_date), function ($q) use ($request) {
                      $q->where('end_date', '<=', ($request->end_date . ' 23:59:59'));
                  });
            })
                ->where(
                    [
                        "users.business_id" => $business_id
                    ]
                )
                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('leaves.status', $request->status);
                })
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
                    return $query->whereHas("leaves", function($q)use ($request)  {
                        $q->where('user_id', $request->user_id);
                    });
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                        $query->whereNotIn("users.id",[auth()->user()->id]);
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


$total_leave_hours = 0;

$employee->datewise_leave = collect($dateArray)->map(function ($date) use ($employee,&$total_leave_hours) {


   $leave_record = LeaveRecord::whereHas(
        "leave.employee", function($query) use($employee,$date) {
             $query->where([
                "users.id" => $employee->id,
                "leave_records.date" => $date
             ]);

        }
    )
    ->first();

    $leave_hours = 0;
    if($leave_record) {
        $startTime = Carbon::parse($leave_record->start_time);
        $endTime = Carbon::parse($leave_record->end_time);
        $leave_hours = $startTime->diffInHours($endTime);
        $total_leave_hours += $leave_hours;
    }

if($leave_record) {
    return [
        'date' => Carbon::parse($date)->format("d-m-Y"),
        'is_on_leave' => $leave_record?1:0,
        'leave_hours' => $leave_hours,

    ];
}
return null;

})->filter()->values();

$employee->total_leave_hours = $total_leave_hours;
$employee->unsetRelation('leaves');
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
     *      path="/v4.0/leaves",
     *      operationId="getLeavesV4",
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
     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *  *  * @OA\Parameter(
 *     name="status",
 *     in="query",
 *     description="status",
 *     required=true,
 *     example="status"
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

     public function getLeavesV4(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('leave_view')) {
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
             $leaves = Leave::with([
                 "employee" => function ($query) {
                     $query->select(
                         'users.id',
                         'users.first_Name',
                         'users.middle_Name',
                         'users.last_Name',
                         'users.image'
                     );
                 },
                 "employee.departments" => function ($query) {
                     // You can select specific fields from the departments table if needed
                     $query->select(
                         'departments.id',
                         'departments.name',
                        //  "departments.location",
                         "departments.description"
                     );
                 },
                 "leave_type" => function ($query) {
                     $query->select(
                         'setting_leave_types.id',
                         'setting_leave_types.name',
                         'setting_leave_types.type',
                         'setting_leave_types.amount',

                     );
                 },

             ])
                 ->where(
                     [
                         "leaves.business_id" => $business_id
                     ]
                 )
                 ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 })
                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         // $query->where("leaves.name", "like", "%" . $term . "%")
                         //     ->orWhere("leaves.description", "like", "%" . $term . "%");
                     });
                 })
                 ->when(!empty($request->user_id), function ($query) use ($request) {
                     return $query->where('leaves.user_id', $request->user_id);
                 })
                 ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("employee", function ($query){
                        $query->whereNotIn("users.id",[auth()->user()->id]);
                    });
                })

                 //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                 //        return $query->where('product_category_id', $request->product_category_id);
                 //    })
                 ->when(!empty($request->start_date), function ($query) use ($request) {
                    $query->where('leaves.start_date', '>=', $request->start_date . ' 00:00:00');

                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                    $query->where('leaves.end_date', '<=', $request->end_date);
                 })
                 ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('leaves.status', $request->status);
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
                 });



                 foreach ($leaves as $leave) {
                    $leave->total_leave_hours = $leave->records->sum(function ($record) {
                     $startTime = Carbon::parse($record->start_time);
                     $endTime = Carbon::parse($record->end_time);
                     return $startTime->diffInHours($endTime);

                    });

                 }
             $data["data"] = $leaves;


             $data["data_highlights"] = [];

            //  $data["data_highlights"]["employees_on_leave"] = $leaves->count();

            //  $data["data_highlights"]["total_leave_hours"] = $leaves->reduce(function ($carry, $leave) {
            //      return $carry + $leave->records->sum(function ($record) {
            //          $startTime = \Carbon\Carbon::parse($record->start_time);
            //          $endTime = \Carbon\Carbon::parse($record->end_time);

            //          return $startTime->diffInHours($endTime);
            //      });
            //  }, 0);

            //  $data["data_highlights"]["single_day_leaves"] = $leaves->filter(function ($leave) {
            //      return $leave->leave_duration == "single_day";
            //  })->count();

            //  $data["data_highlights"]["multiple_day_leaves"] = $leaves->filter(function ($leave) {
            //      return $leave->leave_duration == "multiple_day";
            //  })->count();

            $data["data_highlights"]["leave_approved_hours"] = $leaves->filter(function ($leave) {
                return ($leave->status == "approved");
            })->sum('total_leave_hours');

            $data["data_highlights"]["leave_approved_total_individual_days"] = $leaves->filter(function ($leave) {

                return ($leave->status == "approved");
            })->sum(function ($leave) {
                return $leave->records->count();
            });



            $data["data_highlights"]["upcoming_leaves_hours"] = $leaves->filter(function ($leave) {

                return Carbon::parse($leave->start_date)->isFuture();
            })->sum(function ($leave) {
                return $leave->records->count();
            });

            $data["data_highlights"]["upcoming_leaves_total_individual_days"] = $leaves->filter(function ($leave) {

                return Carbon::parse($leave->start_date)->isFuture();
            })->sum('total_leave_hours');




            $data["data_highlights"]["pending_leaves"] = $leaves->filter(function ($leave) {

                return ($leave->status != "approved");
            })->count();

             return response()->json($data, 200);
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('leave_view')) {
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
            $leave =  Leave::where([
                "id" => $id,
                "business_id" => $business_id
            ])
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
                ->first();
            if (!$leave) {
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
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
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('leave_delete')) {
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
            $existingIds = Leave::where([
                "business_id" => $business_id
            ])
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })

             ->whereHas("employee", function ($query){
                    $query->whereNotIn("users.id",[auth()->user()->id]);
                })

                ->whereIn('id', $idsArray)
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



            $leaves =  Leave::whereIn("id",$existingIds)->get();

            foreach ($leaves as $leave) {
                $leave_history_data = $leave->toArray();
                $leave_history_data['leave_id'] = $leave->id;
                $leave_history_data['actor_id'] = auth()->user()->id;
                $leave_history_data['action'] = "delete";
                $leave_history_data['is_approved'] = NULL;
                $leave_history_data['leave_created_at'] = $leave->created_at;
                $leave_history_data['leave_updated_at'] = $leave->updated_at;
                // $leave_history = LeaveHistory::create($leave_history_data);



                // $leave_record_history = $leave->records->toArray();
                // $leave_record_history["leave_id"] = $leave_history->id;
                // $leave_history->records()->createMany($leave_record_history);


            }





            Leave::destroy($existingIds);

            $this->send_notification($leaves, $leaves->first()->employee, "Leave Request Deleted", "delete", "leave");

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
