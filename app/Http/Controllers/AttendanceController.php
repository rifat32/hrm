<?php

namespace App\Http\Controllers;

use App\Exports\AttendancesExport;
use App\Http\Requests\AttendanceApproveRequest;
use App\Http\Requests\AttendanceBypassMultipleCreateRequest;
use App\Http\Requests\AttendanceCreateRequest;
use App\Http\Requests\AttendanceMultipleCreateRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Http\Utils\AttendanceUtil;
use App\Http\Utils\BasicNotificationUtil;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\PayrunUtil;
use App\Http\Utils\UserActivityUtil;
use App\Jobs\SendNotificationJob;
use App\Models\Attendance;

use App\Models\AttendanceHistory;
use App\Models\Department;
use App\Models\WorkShiftHistory;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\Payroll;
use App\Models\PayrollAttendance;
use App\Models\Role;
use App\Models\SettingAttendance;
use App\Models\User;
use App\Models\UserProject;
use App\Observers\AttendanceObserver;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, PayrunUtil, BasicNotificationUtil, AttendanceUtil, BasicUtil;



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
     *
     * *     @OA\Property(property="attendance_records", type="string", format="array", example={
     * {
     * "in_time":"00:44:00",
     * "out_time":"00:45:00"
     * },
     * * {
     * "in_time":"00:48:00",
     *  "out_time":"00:50:00"
     * }
     *
     * }),
     *

     *
     *
     *
     *
     *
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

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!auth()->user()->hasPermissionTo('attendance_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            // Retrieve attendance setting
            $setting_attendance = $this->get_attendance_setting();
            $attendance_data = $this->process_attendance_data($request_data, $setting_attendance, $request_data["user_id"]);


            // Assign additional data to request data for attendance creation
            $attendance =  Attendance::create($attendance_data);




            $this->send_notification($attendance, $attendance->employee, "Attendance Taken", "create", "attendance");

            DB::commit();
            return response($attendance, 201);
        } catch (Exception $e) {
            DB::rollBack();
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
        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('attendance_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();
            $setting_attendance = $this->get_attendance_setting();



            $attendances_data = collect($request_data["attendance_details"])->map(function ($item) use ($request_data, $setting_attendance) {
            $item = $this->process_attendance_data($item, $setting_attendance, $request_data["user_id"]);
                return  $item;
            });
            $employee = User::where([
                "id" => $request_data["user_id"]
            ])
                ->first();

            if (!$employee) {
                return response()->json([
                    "message" => "someting_went_wrong", 500
                ]);
            }


            $created_attendances = $employee->attendances()->createMany($attendances_data);

            if (!empty($created_attendances)) {
                $this->send_notification($created_attendances, $employee, "Attendance Taken", "create", "attendance");
            }


            DB::commit();
            if (!empty($created_attendances)) {
                return response(['attendances' => $created_attendances], 201);
            } else {
                // Handle the case where records were not successfully created
                return response(['error' => 'Failed to create attendance records'], 500);
            }
            return response([], 201);
        } catch (Exception $e) {

            DB::rollBack();
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
     *
     *
     *
      * *     @OA\Property(property="attendance_records", type="string", format="array", example={
     * {
     * "in_time":"00:44:00",
     * "out_time":"00:45:00"
     * },
     * * {
     * "in_time":"00:48:00",
     *  "out_time":"00:50:00"
     * }
     *
     * }),
     *
     *
     *
     *
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

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('attendance_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $request_data = $request->validated();

            // Retrieve attendance setting
            $setting_attendance = $this->get_attendance_setting();

            // Process attendance data for update
            $attendance_data = $this->process_attendance_data($request_data, $setting_attendance, $request_data["user_id"]);


            $attendance_query_params = [
                "id" => $request_data["id"],
                "business_id" => auth()->user()->business_id
            ];

            $attendance = Attendance::where($attendance_query_params)->first();
            if ($attendance) {
                $attendance->fill(collect($attendance_data)->only([
                    'note',
                    "in_geolocation",
                    "out_geolocation",
                    'user_id',
                    'in_date',
                    'does_break_taken',

                    "behavior",
                    "capacity_hours",
                    "work_hours_delta",
                    "break_type",
                    "break_hours",
                    "total_paid_hours",
                    "regular_work_hours",
                    "work_shift_start_at",
                    "work_shift_end_at",
                    "work_shift_history_id",
                    "holiday_id",
                    "leave_record_id",
                    "is_weekend",

                    "overtime_hours",
                    "punch_in_time_tolerance",
                    "status",
                    'work_location_id',
                    'project_id',
                    "is_active",
                    // "business_id",
                    // "created_by",
                    "regular_hours_salary",
                    "overtime_hours_salary",
                ])->toArray());
                $attendance->save();
            }


            $observer = new AttendanceObserver();
            $observer->updated($attendance, 'update');

            $this->adjust_payroll_on_attendance_update($attendance,1);



            $this->send_notification($attendance, $attendance->employee, "Attendance updated", "update", "attendance");
            DB::commit();

            return response($attendance, 201);
        } catch (Exception $e) {
            DB::rollBack();
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

        DB::beginTransaction();
        try {

            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            // Check permission to approve attendance
            if (!$request->user()->hasPermissionTo("attendance_approve")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // Extract data
            $request_data = $request->validated();


            // Fetch attendance and setting
            $setting_attendance = $this->get_attendance_setting();
            $attendance_query_params = [
                "id" => $request_data["attendance_id"],
                "business_id" => auth()->user()->business_id
            ];
            $attendance = $this->find_attendance($attendance_query_params);




            // Fetch user details
            $user = User::where([
                "id" =>  auth()->user()->id
            ])
                ->first();

            // Update attendance status based on user's permissions and roles
            if ($this->is_special_user($user, $setting_attendance) || $this->is_special_role($user, $setting_attendance) || $user->hasRole("business_owner")) {
                $attendance->status = $request_data["is_approved"] ? "approved" : "rejected";
            }

            // Save the updated attendance
            $attendance->save();

            // Update observer with approval
            $observer = new AttendanceObserver();
            $observer->updated($attendance, 'approve');

            // Adjust payroll based on attendance update
            $this->adjust_payroll_on_attendance_update($attendance,1);

            // Determine notification message based on attendance status
            $message = $attendance->status == "approved" ? "Attendance approved" : "Attendance rejected";

            // Send notification
            $this->send_notification($attendance, $attendance->employee, $message, $attendance->status, "attendance");



            DB::commit();
            return response($attendance, 200);
        } catch (Exception $e) {
            DB::rollBack();
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
     *
     ** @OA\Parameter(
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

            $all_manager_department_ids = $this->get_all_departments_of_manager();

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
                    $idsArray = explode(',', $request->user_id);
                    return $query->whereIn('attendances.user_id', $idsArray);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("employee", function ($query) {
                        $query->whereNotIn("users.id", [auth()->user()->id]);
                    });
                })

                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('attendances.status', $request->status);
                })

                ->when(!empty($request->overtime), function ($query) use ($request) {
                    $number_query = explode(',', str_replace(' ', ',', $request->overtime));
                    return $query->where('attendances.overtime_hours', $number_query);
                })


                ->when(!empty($request->schedule_hour), function ($query) use ($request) {
                    $number_query = explode(',', str_replace(' ', ',', $request->schedule_hour));
                    return $query->where('attendances.capacity_hours', $number_query);
                })

                ->when(!empty($request->break_hour), function ($query) use ($request) {
                    $number_query = explode(',', str_replace(' ', ',', $request->break_hour));
                    return $query->where('attendances.break_hours', $number_query);
                })

                ->when(!empty($request->worked_hour), function ($query) use ($request) {
                    $number_query = explode(',', str_replace(' ', ',', $request->worked_hour));
                    return $query->where('attendances.total_paid_hours', $number_query[0], $number_query[1]);
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
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
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

            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $business_id =  $request->user()->business_id;
            $setting_attendance = $this->get_attendance_setting();

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
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("employee", function ($query) {
                        $query->whereNotIn("users.id", [auth()->user()->id]);
                    });
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        // $query->where("attendances.name", "like", "%" . $term . "%")
                        //     ->orWhere("attendances.description", "like", "%" . $term . "%");
                    });
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
                });






            $data['data'] = $attendances;

            $behavior_counts = [
                'absent' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'absent')->count(),
                'regular' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'regular')->count(),
                'early' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'early')->count(),
                'late' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'late')->count(),
            ];

            $max_behavior = max($behavior_counts);
            $data['data_highlights']['behavior'] = $behavior_counts;
            $data['data_highlights']['average_behavior'] = array_search($max_behavior, $behavior_counts);
            $data['data_highlights']['total_schedule_hours'] = $attendances->sum('capacity_hours');

            // $data['data_highlights']['total_leave_hours'] =  $attendances->sum('leave_hours');

            $data['data_highlights']['total_leave_hours'] =  0;

            $total_available_hours = $data['data_highlights']['total_schedule_hours'] - $data['data_highlights']['total_leave_hours'];

            if ($total_available_hours == 0 || $data['data_highlights']['total_schedule_hours'] == 0) {
                $data['data_highlights']['total_work_availability_per_centum'] = 0;
            } else {
                $data['data_highlights']['total_work_availability_per_centum'] = ($total_available_hours / $data['data_highlights']['total_schedule_hours']) * 100;
            }

            if (!empty($setting_attendance->work_availability_definition)) {
                if ($attendances->isEmpty()) {
                    $data['data_highlights']['work_availability'] = 'no data';
                } elseif ($data['data_highlights']['total_work_availability_per_centum'] >= $setting_attendance->work_availability_definition) {
                    $data['data_highlights']['work_availability'] = 'good';
                } else {
                    $data['data_highlights']['work_availability'] = 'bad';
                }
            } else {
                $data['data_highlights']['work_availability'] = 'good';
            }

            $data['data_highlights']['total_active_hours'] = $attendances->sum('total_paid_hours');
            $data['data_highlights']['total_extra_hours'] = $attendances->sum('overtime_hours');

            return response()->json($data, 200);
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

            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $business_id =  $request->user()->business_id;

            $start_date = !empty($request->start_date) ? $request->start_date : Carbon::now()->startOfYear()->format('Y-m-d');
            $end_date = !empty($request->end_date) ? $request->end_date : Carbon::now()->endOfYear()->format('Y-m-d');


            $employees = User::with(
                ["departments"]
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
                    });
                })

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("attendances", function ($q) use ($request) {
                        $idsArray = explode(',', $request->user_id);
                        $q->whereIn('attendances.user_id', $idsArray);
                    });
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



                // Parse start and end dates
                $startDate = Carbon::parse(($start_date . ' 00:00:00'));
                $endDate = Carbon::parse(($end_date . ' 23:59:59'));

                // Create an array of dates within the given range
                $dateArray = [];
                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    $dateArray[] = $date->format('Y-m-d');
                }

                // Get employee IDs
                $employee_ids = $employees->pluck("id");
                // Retrieve leave records within the specified date range
                $leave_records = LeaveRecord::whereHas('leave',    function ($query) use ($employee_ids) {
                    $query->whereIn("leaves.user_id",  $employee_ids)
                        ->where("leaves.status", "approved");
                })
                    ->where('date', '>=', $start_date . ' 00:00:00')
                    ->where('date', '<=', ($end_date . ' 23:59:59'))
                    ->get();

                // Retrieve attendance records within the specified date range
                $attendances = Attendance::where("attendances.status", "approved")
                    ->whereIn('attendances.user_id', $employee_ids)
                    ->where('attendances.in_date', '>=', $start_date . ' 00:00:00')
                    ->where('attendances.in_date', '<=', ($end_date . ' 23:59:59'))

                    ->get();

                // Iterate over each employee
                $employees->each(function ($employee) use ($dateArray, $attendances, $leave_records) {


                    // Get all parent department IDs of the employee
                    $all_parent_department_ids = $this->all_parent_departments_of_user($employee->id);


                    // Initialize total variables
                    $total_paid_hours = 0;
                    $total_paid_leave_hours = 0;
                    $total_paid_holiday_hours = 0;
                    $total_leave_hours = 0;
                    $total_capacity_hours = 0;
                    $total_balance_hours = 0;

                    // Map date-wise attendance for the employee
                    $employee->datewise_attendanes = collect($dateArray)->map(
                        function ($date) use ($attendances, $leave_records, &$total_balance_hours, &$total_paid_hours, &$total_capacity_hours, &$total_leave_hours, &$total_paid_leave_hours, &$total_paid_holiday_hours, $employee, $all_parent_department_ids) {
                            // Get holiday details
                            $holiday = $this->get_holiday_details($date, $employee->id, $all_parent_department_ids);

                            // Find attendance record for the given date and employee
                            $attendance = $attendances->first(function ($attendance) use ($date, $employee) {
                                $in_date = Carbon::parse($attendance->in_date)->format("Y-m-d");
                                return (($in_date == $date) && ($attendance->user_id == $employee->id));
                            });
                            // Find leave record for the given date and employee, also calculate total leave hours
                            $leave_record = $leave_records->first(function ($leave_record) use ($date, $employee, &$total_leave_hours) {
                                $leave_date = Carbon::parse($leave_record->date)->format("Y-m-d");
                                if (($leave_record->user_id != $employee->id) || ($date != $leave_date)) {
                                    return false;
                                }
                                $total_leave_hours += $leave_record->leave_hours;
                                return true;
                            });
                            // Initialize result variables
                            $result_is_present = 0;
                            $result_paid_hours = 0;
                            $result_balance_hours = 0;

                            // Calculate paid leave hours if leave record exists and it's a paid leave
                            if ($leave_record) {
                                if ($leave_record->leave->leave_type->type == "paid") {
                                    $paid_leave_hours =  $leave_record->leave_hours;
                                    $total_paid_leave_hours += $paid_leave_hours;
                                    $result_paid_hours += $paid_leave_hours;
                                    $total_paid_hours +=  $paid_leave_hours;
                                }
                            }
                            // Calculate holiday hours if holiday exists
                            if ($holiday) {
                                if (!$employee->weekly_contractual_hours || !$employee->minimum_working_days_per_week) {
                                    $holiday_hours = 0;
                                } else {
                                    $holiday_hours = $employee->weekly_contractual_hours / $employee->minimum_working_days_per_week;
                                }

                                $total_paid_holiday_hours += $holiday_hours;
                                $result_paid_hours += $holiday_hours;
                                $total_paid_hours += $holiday_hours;
                            }

                            // Update result variables based on attendance
                            if ($attendance) {
                                $total_capacity_hours += $attendance->capacity_hours;
                                if ($attendance->total_paid_hours > 0) {
                                    $result_is_present = 1;

                                    $result_balance_hours = $attendance->overtime_hours;
                                    $total_paid_hours += $attendance->total_paid_hours;
                                    $total_balance_hours += $attendance->overtime_hours;
                                    $result_paid_hours += $attendance->total_paid_hours;
                                }
                            }
                            // Prepare and return the result array
                            if ($leave_record || $attendance || $holiday) {
                                return [
                                    'date' => Carbon::parse($date)->format("d-m-Y"),
                                    'is_present' => $result_is_present,
                                    'paid_hours' => $result_paid_hours,
                                    "result_balance_hours" => $result_balance_hours,
                                    'capacity_hours' => $attendance ? $attendance->capacity_hours : 0,
                                    "paid_leave_hours"   => $leave_record ? (($leave_record->leave->leave_type->type == "paid") ? $leave_record->leave_hours : 0) : 0
                                ];
                            }
                            // If no relevant record found, return null
                            return  null;
                        }
                    )
                        ->filter()
                        ->values();

                    // Assign total variables to employee object
                    $employee->total_balance_hours = $total_balance_hours;
                    $employee->total_leave_hours = $total_leave_hours;
                    $employee->total_paid_leave_hours = $total_paid_leave_hours;
                    $employee->total_paid_holiday_hours = $total_paid_holiday_hours;
                    $employee->total_paid_hours = $total_paid_hours;
                    $employee->total_capacity_hours = $total_capacity_hours;
                    return $employee;
                });



            // Return JSON response with employees data
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
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $business_id =  $request->user()->business_id;

            $attendance =  Attendance::with("employee")->where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })

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
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Attendance::where([
                "business_id" => $business_id
            ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->whereHas("employee", function ($query) {
                    $query->whereNotIn("users.id", [auth()->user()->id]);
                })

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

            $attendances =  Attendance::whereIn("id", $existingIds)->get();



            $payrolls = Payroll::whereHas("payroll_attendances", function ($query) use ($existingIds) {
                $query->whereIn("payroll_attendances.attendance_id", $existingIds);
            })->get();

            PayrollAttendance::whereIn("attendance_id", $existingIds)
                ->delete();



            $this->recalculate_payrolls($payrolls);


            Attendance::destroy($existingIds);
            $this->send_notification($attendances, $attendances->first()->employee, "Attendance deleted", "delete", "attendance");

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }














    /**
     *
     * @OA\Post(
     *      path="/v1.0/attendances/bypass/multiple",
     *      operationId="createMultipleBypassAttendance",
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
     *    @OA\Property(property="user_ids", type="string", format="array", example={1,2,3}),
     *    *    @OA\Property(property="start_date", type="string", format="string", example="date"),
     *    *    *    @OA\Property(property="end_date", type="string", format="string", example="date"),
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

    public function createMultipleBypassAttendance(AttendanceBypassMultipleCreateRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            // Check if the user is authorized to perform this action
            if (!$request->user()->hasRole('business_owner')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            // Validate the request data
            $request_data = $request->validated();



            // Retrieve attendance setting
            $setting_attendance = $this->get_attendance_setting();

            // Retrieve users based on request data
            if (empty($request_data["user_ids"])) {
                $users  =  User::where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->get();
            } else {
                $users  =  User::where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->whereIn("id", $request_data["user_ids"])
                    ->get();
            }

            // Iterate over each user
            foreach ($users as $user) {

                // Parse start and end dates
                $start_date = Carbon::parse($request_data["start_date"]);
                $end_date = Carbon::parse($request_data["end_date"]);


                // Create date range between start and end dates
                $date_range = $start_date->daysUntil($end_date->addDay());

                $attendance_details = [];
                // Map date range to create attendance details
                 $date_range->map(function ($date) use ($user) {
                    $temp_data["in_date"] = $date;
                    $temp_data["does_break_taken"] = 1;
                    $temp_data["project_id"] = UserProject::where([
                        "user_id" => $user->id
                    ])
                        ->first()->project_id;
                    $temp_data["work_location_id"] = $user->work_location_id;


                    $attendance_details[] = $temp_data;
                });




                // Get all parent department IDs of the employee
                $all_parent_department_ids = $this->all_parent_departments_of_user($user->id);

                // Map attendance details to create attendances data
               $attendances_data =  collect($attendance_details)->map(function ($item) use ($setting_attendance, $user, $all_parent_department_ids) {

                    // Retrieve work shift history for the user and date
                    $user_salary_info = $this->get_salary_info($user->user_id, $item["in_date"]);

                    // Retrieve work shift history for the user and date
                    $work_shift_history =  $this->get_work_shift_history($item["in_date"], $user->id);
                    // Retrieve work shift details based on work shift history and date
                    $work_shift_details =  $this->get_work_shift_details($work_shift_history, $item["in_date"]);

                    if($work_shift_history->is_flexible) {
                         return false;
                    }



                    // flexible error

                    $item["attendance_record"][0]["in_time"] = $work_shift_details->start_at;
                    $item["attendance_record"][0]["out_time"] = $work_shift_details->end_at;

                    // Prepare data for attendance creation
                    $attendance_data = $this->prepare_data_on_attendance_create($item, $user->id);
                    $attendance_data["status"] = "approved";

                    // Retrieve salary information for the user and date
                    $user_salary_info = $this->get_salary_info($user->id, $attendance_data["in_date"]);

                    // Retrieve holiday details for the user and date
                    $holiday = $this->get_holiday_details($item["in_date"], $user->id, $all_parent_department_ids);

                    if ($holiday && $holiday->is_active) {
                        return false;
                    }
                    // Retrieve leave record details for the user and date
                    $leave_record = $this->get_leave_record_details($attendance_data["in_date"], $user->id, $attendance_data["attendance_records"]);

                    if ($leave_record) {
                        return false;
                    }

                    // Calculate capacity hours based on work shift details
                    $capacity_hours = $this->calculate_capacity_hours($work_shift_details);


                    $total_present_hours = $this->calculate_total_present_hours($attendance_data["attendance_records"]);

                    // Adjust paid hours based on break taken and work shift history
                    $total_paid_hours = $this->adjust_paid_hours($attendance_data["does_break_taken"], $total_present_hours, $work_shift_history);



                    // Prepare attendance data
                    $attendance_data["break_type"] = $work_shift_history->break_type;
                    $attendance_data["break_hours"] = $work_shift_history->break_hours;
                    $attendance_data["behavior"] = "regular";
                    $attendance_data["capacity_hours"] = $capacity_hours;
                    $attendance_data["work_hours_delta"] = 0;
                    $attendance_data["total_paid_hours"] = $total_paid_hours;
                    $attendance_data["regular_work_hours"] = $total_paid_hours;
                    $attendance_data["work_shift_start_at"] = $work_shift_details->start_at;
                    $attendance_data["work_shift_end_at"] =  $work_shift_details->end_at;
                    $attendance_data["work_shift_history_id"] = $work_shift_history->id;
                    $attendance_data["holiday_id"] = $holiday ? $holiday->id : NULL;
                    $attendance_data["leave_record_id"] = $leave_record ? $leave_record->id : NULL;
                    $attendance_data["is_weekend"] = $work_shift_details->is_weekend;
                    $attendance_data["overtime_hours"] = 0;
                    $attendance_data["punch_in_time_tolerance"] = $setting_attendance->punch_in_time_tolerance;
                    $attendance_data["regular_hours_salary"] =   $total_paid_hours * $user_salary_info["hourly_salary"];
                    $attendance_data["overtime_hours_salary"] =   0;

                    return $attendance_data;

                });


                $created_attendances = $user->attendances()->createMany($attendances_data);

                if (!empty($created_attendances)) {
                    $this->send_notification($created_attendances, $user, "Attendance Taken", "create", "attendance");
                }


            }
            DB::commit();
            return response(["ok" => true], 201);
        } catch (Exception $e) {
            DB::rollBack();
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
}
