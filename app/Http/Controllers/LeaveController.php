<?php

namespace App\Http\Controllers;

use App\Exports\LeavesExport;
use App\Http\Components\AuthorizationComponent;
use App\Http\Components\DepartmentComponent;
use App\Http\Components\HolidayComponent;
use App\Http\Components\LeaveComponent;
use App\Http\Components\WorkShiftHistoryComponent;
use App\Http\Requests\LeaveApproveRequest;
use App\Http\Requests\LeaveBypassRequest;
use App\Http\Requests\LeaveCreateRequest;
use App\Http\Requests\LeaveUpdateRequest;
use App\Http\Requests\MultipleFileUploadRequest;
use App\Http\Utils\BasicNotificationUtil;
use App\Http\Utils\BasicUtil;
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
use App\Models\LeaveRecordArrear;
use App\Models\Payroll;
use App\Models\PayrollAttendance;
use App\Models\PayrollLeaveRecord;
use App\Models\Role;
use App\Models\SettingLeave;
use App\Models\User;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use App\Observers\LeaveObserver;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;


class LeaveController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, LeaveUtil, PayrunUtil, BasicNotificationUtil;

    protected $authorizationComponent;
    protected $leaveComponent;
    protected $departmentComponent;
    protected $workShiftHistoryComponent;
    protected $holidayComponent;

    public function __construct(AuthorizationComponent $authorizationComponent, LeaveComponent $leaveComponent, DepartmentComponent $departmentComponent, WorkShiftHistoryComponent $workShiftHistoryComponent, HolidayComponent $holidayComponent)
    {
        $this->authorizationComponent = $authorizationComponent;
        $this->leaveComponent = $leaveComponent;
        $this->departmentComponent = $departmentComponent;
        $this->workShiftHistoryComponent = $workShiftHistoryComponent;
        $this->holidayComponent = $holidayComponent;
    }

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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

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
     *   @OA\Property(property="hourly_rate", type="number", format="number", example="5"),
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

    public function createLeave(LeaveCreateRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            $this->authorizationComponent->hasPermission('leave_create');

            $request_data = $request->validated();
            $processed_leave_data = $this->leaveComponent->processLeaveRequest($request_data);

            $leave =  Leave::create($processed_leave_data["leave_data"]);
            $leave->records()->createMany($processed_leave_data["leave_record_data_list"]);

            $this->leaveComponent->validateLeaveAvailability($leave);


            foreach ($leave->records as $leave_record) {
                $this->adjust_payroll_on_leave_update($leave_record,0);
            }


            $leaveObserver = new LeaveObserver();
            $leaveObserver->create($leave);

            // $leave_history_data = $leave->toArray();
            // $leave_history_data['leave_id'] = $leave->id;
            // $leave_history_data['actor_id'] = auth()->user()->id;
            // $leave_history_data['action'] = "create";
            // $leave_history_data['is_approved'] = NULL;
            // $leave_history_data['leave_created_at'] = $leave->created_at;
            // $leave_history_data['leave_updated_at'] = $leave->updated_at;
            // $leave_history = LeaveHistory::create($leave_history_data);
            // $leave_history->records()->createMany($leave->records->toArray());


            $this->send_notification($leave, $leave->employee, "Leave Request Taken", "create", "leave");

            DB::commit();

            return response($leave, 200);
        } catch (Exception $e) {
            DB::rollBack();
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

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

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



                $process_leave_approval =   $this->processLeaveApproval($request_data["leave_id"], $request_data["is_approved"]);
                if (!$process_leave_approval["success"]) {

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


                foreach ($leave->records as $leave_record) {
                    $this->adjust_payroll_on_leave_update($leave_record,$request_data["add_in_next_payroll"]);
                }

                if(!empty($request_data["add_in_next_payroll"]) && ($leave->status ==
                "approved")) {
                    LeaveRecordArrear::
                    whereHas("leave_record",function($query) use ($leave) {
                      $query
                      ->whereIn("leave_records.id",$leave->records()->pluck("leave_records.id"));
                    })
                    ->update([ "status" => "approved"]);

                }


                if ($request_data["is_approved"]) {
                    $this->send_notification($leave, $leave->employee, "Leave Request Approved", "approve", "leave");
                } else {
                    $this->send_notification($leave, $leave->employee, "Leave Request Rejected", "reject", "leave");
                }


             DB::commit();
                return response($leave_approval, 201);

        } catch (Exception $e) {
            DB::rollBack();
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
     *      @OA\Property(property="leave_id", type="number", format="number", example="Updated Christmas"),
     *    @OA\Property(property="add_in_next_payroll", type="number", format="number", example="1")
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

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

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


                foreach ($leave->records as $leave_record) {
                    $this->adjust_payroll_on_leave_update($leave_record,$request_data["add_in_next_payroll"]);
                }

                $leave_history_data = $leave->toArray();
                $leave_history_data['leave_id'] = $leave->id;
                $leave_history_data['actor_id'] = auth()->user()->id;
                $leave_history_data['action'] = "bypass";
                $leave_history_data['is_approved'] = NULL;
                $leave_history_data['leave_created_at'] = $leave->created_at;
                $leave_history_data['leave_updated_at'] = $leave->updated_at;


                $this->send_notification($leave, $leave->employee, "Leave Request Approved", "approve", "leave");

                DB::commit();
                return response($leave, 200);

        } catch (Exception $e) {
            DB::rollBack();
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
     *    *   @OA\Property(property="hourly_rate", type="number", format="number", example="5"),
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

        DB::beginTransaction();
        try {

            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
                if (!$request->user()->hasPermissionTo('leave_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();

                $processed_leave_data = $this->leaveComponent->processLeaveRequest($request_data);

                $leave_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];

                $leave  =  tap(Leave::where($leave_query_params))->update(
                    collect($processed_leave_data["leave_data"])->only([
                        'leave_duration',
                        'day_type',
                        'leave_type_id',
                        'user_id',
                        'date',
                        'note',
                        'start_date',
                        'end_date',

                        'attachments',
                        "hourly_rate"
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


                // Get the IDs of existing leave records
$existingRecordIds = $leave->records()->pluck('id')->toArray();

// Delete records that don't exist in the new data
$recordsToDelete = array_diff($existingRecordIds, array_column($processed_leave_data["leave_record_data_list"], 'id'));


// Update or create new records
foreach ($processed_leave_data["leave_record_data_list"] as $recordData) {
    $record = $leave->records()->find($recordData['id']);
    if ($record) {
        // Update existing record
        $record->update($recordData);
    } else {
        // Create new record
        $leave->records()->create($recordData);
    }
}



$this->leaveComponent->validateLeaveAvailability($leave);


$payrolls = Payroll::whereHas("payroll_leave_records", function ($query) use ($recordsToDelete) {
    $query->whereIn("payroll_leave_records.leave_record_id", $recordsToDelete);
})->get();

PayrollLeaveRecord::whereIn("leave_record_id", $recordsToDelete)
    ->delete();

$this->recalculate_payrolls($payrolls);


$leave->records()->whereIn('id', $recordsToDelete)->delete();





                foreach ($leave->records as $leave_record) {
                    $this->adjust_payroll_on_leave_update($leave_record,0);
                }



                $leave_history_data = $leave->toArray();
                $leave_history_data['leave_id'] = $leave->id;
                $leave_history_data['actor_id'] = auth()->user()->id;
                $leave_history_data['action'] = "update";
                $leave_history_data['is_approved'] = NULL;
                $leave_history_data['leave_created_at'] = $leave->created_at;
                $leave_history_data['leave_updated_at'] = $leave->updated_at;
                $leave_history = LeaveHistory::create($leave_history_data);

                $leave_record_history = $leave->records->toArray();
                $leave_record_history["leave_id"] = $leave_history->id;
                $leave_history->records()->createMany($processed_leave_data["leave_record_data_list"]);



                $this->send_notification($leave, $leave->employee, "Leave Request Updated", "update", "leave");

                DB::commit();
                return response($leave, 201);

        } catch (Exception $e) {
            DB::rollBack();
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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('leave_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();


            $business_id =  $request->user()->business_id;
            $leaves = Leave::where(
                [
                    "leaves.business_id" => $business_id
                ]
            )
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
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
                    return $query->whereHas("employee", function ($query) {
                        $query->whereNotIn("users.id", [auth()->user()->id]);
                    });
                })
                ->when(!empty($request->leave_type_id), function ($query) use ($request) {
                    return $query->where('leaves.leave_type_id', $request->leave_type_id);
                })
                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('leaves.status', $request->status);
                })
                ->when(!empty($request->department_id), function ($query) use ($request) {
                    return $query->whereHas("employee.departments", function ($query) use ($request) {
                        $query->where("departments.id", $request->department_id);
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
     *      path="/v1.0/leave-arrears",
     *      operationId="getLeaveArrears",
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
     *      *      * *  @OA\Parameter(
     * name="arrear_status",
     * in="query",
     * description="arrear_status",
     * required=true,
     * example="arrear_status"
     * ),
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

     public function getLeaveArrears(Request $request)
     {
         try {

             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             if (!$request->user()->hasPermissionTo('leave_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();



             $business_id =  $request->user()->business_id;
             $leaves = Leave::where(
                 [
                     "leaves.business_id" => $business_id
                 ]
             )
                 ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                     $query->whereIn("departments.id", $all_manager_department_ids);
                 })

                 ->when(!empty($request->arrear_status),function($query) use($request) {
                    $query->whereHas("records.arrear", function ($query) use ($request) {
                        $query
                        ->where(
                        "leave_record_arrears.status",
                        $request->arrear_status
                        );
                    });
                 },
                 function($query) use($request) {
                    $query->whereHas("records.arrear", function ($query) use ($request) {
                        $query
                        ->whereNotNull(
                        "leave_record_arrears.status"
                        );
                    });
                 }


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
                     return $query->where('leaves.user_id', $request->user_id);
                 })
                 ->when(empty($request->user_id), function ($query) use ($request) {
                     return $query->whereHas("employee", function ($query) {
                         $query->whereNotIn("users.id", [auth()->user()->id]);
                     });
                 })
                 ->when(!empty($request->leave_type_id), function ($query) use ($request) {
                     return $query->where('leaves.leave_type_id', $request->leave_type_id);
                 })
                 ->when(!empty($request->status), function ($query) use ($request) {
                     return $query->where('leaves.status', $request->status);
                 })
                 ->when(!empty($request->department_id), function ($query) use ($request) {
                     return $query->whereHas("employee.departments", function ($query) use ($request) {
                         $query->where("departments.id", $request->department_id);
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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('leave_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();
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
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
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




                ->when(!empty($request->leave_type_id), function ($query) use ($request) {
                    return $query->where('leaves.leave_type_id', $request->leave_type_id);
                })



                ->when(!empty($request->duration), function ($query) use ($request) {
                    $number_query = explode(',', str_replace(' ', ',', $request->duration));
                    return $query->where('leaves.leave_duration', $number_query);
                })



                ->when(!empty($request->total_leave_hours), function ($query) use ($request) {
                    $number_query = explode(',', str_replace(' ', ',', $request->total_leave_hours));
                    return $query->where('leaves.total_leave_hours', $number_query[0],$number_query[1]);
                })


                ->when(!empty($request->date), function ($query) use ($request) {
                    $query->whereHas("records", function($query) use($request){
                        $query->where("leave_records.date",$request->date);
                  });
                })
                ->when(!empty($request->total_leave_hours), function ($query) use ($request) {
                    return $query->whereHas("records", function($query) use($request) {
                        $query->selectRaw("SUM(leave_records.leave_hours) as total_leave_hours")
                              ->groupBy('employee_id'); // Assuming you need to group by employee_id
                    })
                    ->having('total_leave_hours', $request->total_leave_hours);
                })


                ->when(!empty($request->user_id), function ($query) use ($request) {
                    $idsArray = explode(',', $request->user_id);
                    return $query->whereIn('leaves.user_id', $idsArray);
                })

                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("employee", function ($query) {
                        $query->whereNotIn("users.id", [auth()->user()->id]);
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


            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    $pdf = PDF::loadView('pdf.leaves', ["leaves" => $leaves]);
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {
                    return Excel::download(new LeavesExport($leaves), ((!empty($request->file_name) ? $request->file_name : 'leave') . '.csv'));
                }
            } else {
                return response()->json($data, 200);
            }


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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('leave_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();
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




                ]
            )
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->whereHas("leaves", function ($q) use ($request) {
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
                    return $query->whereHas("leaves", function ($q) use ($request) {
                        $q->where('user_id', $request->user_id);
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

                    $employee->datewise_leave = collect($dateArray)->map(function ($date) use ($employee, &$total_leave_hours) {


                        $leave_record = LeaveRecord::whereHas(
                            "leave.employee",
                            function ($query) use ($employee, $date) {
                                $query->where([
                                    "users.id" => $employee->id,
                                    "leave_records.date" => $date
                                ]);
                            }
                        )
                            ->first();

                        $leave_hours = 0;
                        if ($leave_record) {
                            $startTime = Carbon::parse($leave_record->start_time);
                            $endTime = Carbon::parse($leave_record->end_time);
                            $leave_hours = $startTime->diffInHours($endTime);
                            $total_leave_hours += $leave_hours;
                        }

                        if ($leave_record) {
                            return [
                                'date' => Carbon::parse($date)->format("d-m-Y"),
                                'is_on_leave' => $leave_record ? 1 : 0,
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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('leave_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();
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
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
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
                    return $query->whereHas("employee", function ($query) {
                        $query->whereNotIn("users.id", [auth()->user()->id]);
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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('leave_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();
            $business_id =  $request->user()->business_id;
            $leave =  Leave::where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
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
     * @OA\Get(
     *      path="/v1.0/leaves-get-current-hourly-rate",
     *      operationId="getLeaveCurrentHourlyRate",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *  *
     *    *      *    * *  @OA\Parameter(
     * name="date",
     * in="query",
     * description="date",
     * required=true,
     * example="date"
     * ),
     *
     *      summary="This method is to get leave current hourly rate",
     *      description="This method is to get leave current hourly rate",
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


     public function getLeaveCurrentHourlyRate(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             if (!$request->user()->hasPermissionTo('leave_create')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }

         $salary_info = $this->get_salary_info((!empty($request->user_id)?$request->user_id:auth()->user()->id),(!empty($request->date)?$request->date:today()));
         $salary_info["hourly_salary"] =  number_format($salary_info["hourly_salary"], 2);
         $salary_info["overtime_salary_per_hour"] = number_format($salary_info["overtime_salary_per_hour"],2);
         $salary_info["holiday_considered_hours"] = number_format($salary_info["holiday_considered_hours"],2);


             return response()->json($salary_info, 200);
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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('leave_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Leave::where([
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



            $leaves =  Leave::whereIn("id", $existingIds)->get();

            foreach ($leaves as $leave) {
                $leave_history_data = $leave->toArray();
                $leave_history_data['leave_id'] = $leave->id;
                $leave_history_data['actor_id'] = auth()->user()->id;
                $leave_history_data['action'] = "delete";
                $leave_history_data['is_approved'] = NULL;
                $leave_history_data['leave_created_at'] = $leave->created_at;
                $leave_history_data['leave_updated_at'] = $leave->updated_at;
            }





            $recordsToDelete = LeaveRecord::whereHas("leave", function($query) use ($existingIds) {
                       $query->whereIn("leaves.id",$existingIds);
            })
            ->pluck("leave_records.id");

            $payrolls = Payroll::whereHas("payroll_leave_records", function ($query) use ($recordsToDelete) {
                $query->whereIn("payroll_leave_records.leave_record_id", $recordsToDelete);
            })->get();

            PayrollLeaveRecord::whereIn("leave_record_id", $recordsToDelete)
                ->delete();
            $this->recalculate_payrolls($payrolls);

            Leave::destroy($existingIds);
            $this->send_notification($leaves, $leaves->first()->employee, "Leave Request Deleted", "delete", "leave");

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
