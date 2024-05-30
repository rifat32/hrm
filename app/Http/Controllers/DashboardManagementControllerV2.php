<?php

namespace App\Http\Controllers;

use App\Http\Utils\AttendanceUtil;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Attendance;
use App\Models\JobListing;
use App\Models\LeaveRecord;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class DashboardManagementControllerV2 extends Controller
{
    use ErrorUtil, BusinessUtil, UserActivityUtil, BasicUtil, AttendanceUtil;

    public function leaves(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids,
        $status,
        $show_my_data = false
    ) {

        $data_query  = LeaveRecord::when(
            $show_my_data,
            function ($query)  {
                $query->where('leaves.user_id', auth()->user()->id);
            },
            function ($query) use ($all_manager_department_ids,) {

                $query->whereHas("leave.employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);

                });

            }
        )





            ->whereHas("leave", function ($query) use ($status) {
                $query->where([
                    "leaves.business_id" => auth()->user()->business_id,
                    "leaves.status" => $status
                ]);
            });

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();

        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween('date', [$start_date_of_previous_week, ($end_date_of_previous_week . ' 23:59:59')])->count();

        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();

        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween('date', [$start_date_of_previous_month, ($end_date_of_previous_month . ' 23:59:59')])->count();

        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];
        return $data;
    }


    public function getData($data_query,$dateField,$dates) {

        $data["current_amount"] = clone $data_query;
        $data["current_amount"] = $data["current_amount"]->whereBetween($dateField, [$dates["start_date"], ($dates["end_date"] . ' 23:59:59')])->count();



        $data["last_amount"] = clone $data_query;
        $data["last_amount"] = $data["last_amount"]->whereBetween($dateField, [$dates["previous_start_date"], ($dates["previous_end_date"] . ' 23:59:59')])->count();



        $data["all_data"] = clone $data_query;
        $data["all_data"] = $data["all_data"]->whereBetween($dateField, [$dates["start_date"], ($dates["end_date"] . ' 23:59:59')])->get();

$start_date = Carbon::parse($dates["start_date"]);
$end_date = Carbon::parse(($dates["end_date"] . ' 23:59:59'));
// Initialize an array to hold the counts for each date
$data["data"] = [];

// Loop through each day in the date range
for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
    // Filter the data for the current date
    $filtered_data = $data["all_data"]->filter(function ($item) use ($date) {
        return Carbon::parse($item->created_at)->isSameDay($date);
    });

    // Store the count of records for the current date
    $data["data"][$date->toDateString()] = $filtered_data->count();


}
return $data;

    }
    public function total_employee(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids
    ) {

        $data_query  = User::whereHas("departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->whereNotIn('id', [auth()->user()->id])


            // ->where('is_in_employee', 1)

            ->where('is_active', 1);



        $data["total"] = $data_query->count();


        $data["monthly"] = $this->getData(
            $data_query,
            "users.created_at",
            [
                "start_date" => $start_date_of_this_month,
                "end_date" => $end_date_of_this_month,
                "previous_start_date" => $start_date_of_previous_month,
                "previous_end_date" => $end_date_of_previous_month,
            ]
    );

    $data["weekly"] = $this->getData(
        $data_query,
        "users.created_at",
        [
            "start_date" => $start_date_of_this_week,
            "end_date" => $end_date_of_this_week,
            "previous_start_date" => $start_date_of_previous_week,
            "previous_end_date" => $end_date_of_previous_week,
        ]
);

        return $data;
    }


    public function open_roles(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids
    ) {

        $data_query  = JobListing::where("application_deadline",">=", today())
        ->where("business_id",auth()->user()->business_id);

        $data["total"] = $data_query->count();


        $data["monthly"] = $this->getData(
            $data_query,
            "users.created_at",
            [
                "start_date" => $start_date_of_this_month,
                "end_date" => $end_date_of_this_month,
                "previous_start_date" => $start_date_of_previous_month,
                "previous_end_date" => $end_date_of_previous_month,
            ]
    );

    $data["weekly"] = $this->getData(
        $data_query,
        "application_deadline",
        [
            "start_date" => $start_date_of_this_week,
            "end_date" => $end_date_of_this_week,
            "previous_start_date" => $start_date_of_previous_week,
            "previous_end_date" => $end_date_of_previous_week,
        ]
);


        return $data;
    }

    public function checkHoliday($date,$user_id){
          // Get all parent department IDs of the employee
          $all_parent_department_ids = $this->all_parent_departments_of_user($user_id);
    // Retrieve work shift history for the user and date
    $work_shift_history =  $this->get_work_shift_history($date, $user_id);
    // Retrieve work shift details based on work shift history and date
    $work_shift_details =  $this->get_work_shift_details($work_shift_history, $date);



    if (!$work_shift_details->start_at || !$work_shift_details->end_at || $work_shift_details->is_weekend ) {
        return true;
    }

     // Retrieve holiday details for the user and date
     $holiday = $this->get_holiday_details($date, $user_id, $all_parent_department_ids);

     if (!empty($holiday) && $holiday->is_active) {
         return true;
     }
     // Retrieve leave record details for the user and date
     $leave_record = $this->get_leave_record_details($date, $user_id, [], true);

     if (!empty($leave_record)) {
         return true;
     }


    return false;

    }


    public function calculateAbsent($all_manager_user_ids,$date,$data_query){

        $absent_count = 0;
         foreach($all_manager_user_ids as $user_id){

            if(!$this->checkHoliday($date,$user_id)) {

             $data_query = clone $data_query;
             $attendance = $data_query->where("in_date",$date)->first();
             if(empty($attendance)) {
$absent_count++;
             }

            }
         }
         return $absent_count;

  }

  public function getAbsentData($all_manager_user_ids,$data_query,$dates) {
    $data["current_amount"] = 0;
    $data["last_amount"] = 0;
    $data["data"] = [];

    $start_date = Carbon::parse($dates["start_date"]);
    $end_date = Carbon::parse(($dates["end_date"] . ' 23:59:59'));
    // Loop through each day in the date range
    for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
        // Store the count of records for the current date
        $data["data"][$date->toDateString()] = $this->calculateAbsent($all_manager_user_ids, $date, $data_query);
        $data["current_amount"] = $data["current_amount"] + $data["data"][$date->toDateString()];
    }


$previous_start_date = Carbon::parse($dates["previous_start_date"]);
$previous_end_date = Carbon::parse(($dates["previous_end_date"] . ' 23:59:59'));

    // Loop through each day in the date range
    for ($date = $previous_start_date->copy(); $date->lte($previous_end_date); $date->addDay()) {
        // Store the count of records for the current date
        $previous_data = $this->calculateAbsent($all_manager_user_ids, $date, $data_query);
        $data["last_amount"] = $data["last_amount"] + $previous_data;

    }

    return $data;

  }


    public function absent(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids


    ) {

        $all_manager_user_ids = $this->get_all_user_of_manager($all_manager_department_ids);


        $data_query  = Attendance::where([
            "is_present" => 1
        ]);


        $data["today"] = $this->calculateAbsent($all_manager_user_ids, $today, $data_query);

        $data["monthly"] = $this->getAbsentData(
            $all_manager_user_ids,
            $data_query,
            [
                "start_date" => $start_date_of_this_week,
                "end_date" => $end_date_of_this_week,
                "previous_start_date" => $start_date_of_previous_week,
                "previous_end_date" => $end_date_of_previous_week,
            ]
    );

    $data["weekly"] = $this->getAbsentData(
        $all_manager_user_ids,
        $data_query,
        [
            "start_date" => $start_date_of_this_week,
            "end_date" => $end_date_of_this_week,
            "previous_start_date" => $start_date_of_previous_week,
            "previous_end_date" => $end_date_of_previous_week,
        ]
);


        return $data;
    }


    public function calculatePresent($all_manager_user_ids,$date,$data_query){

        $present_count = 0;
         foreach($all_manager_user_ids as $user_id){

            if(!$this->checkHoliday($date,$user_id)) {

             $data_query = clone $data_query;
             $attendance = $data_query->where("in_date",$date)->first();
             if(!empty($attendance)) {
    $present_count++;
             }

            }
         }
         return $present_count;

    }
    public function getPresentData($all_manager_user_ids,$data_query,$dates) {
        $data["current_amount"] = 0;
        $data["last_amount"] = 0;
        $data["data"] = [];

        $start_date = Carbon::parse($dates["start_date"]);
        $end_date = Carbon::parse(($dates["end_date"] . ' 23:59:59'));
        // Loop through each day in the date range
        for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
            // Store the count of records for the current date
            $data["data"][$date->toDateString()] = $this->calculatePresent($all_manager_user_ids, $date, $data_query);
            $data["current_amount"] = $data["current_amount"] + $data["data"][$date->toDateString()];
        }


    $previous_start_date = Carbon::parse($dates["previous_start_date"]);
    $previous_end_date = Carbon::parse(($dates["previous_end_date"] . ' 23:59:59'));

        // Loop through each day in the date range
        for ($date = $previous_start_date->copy(); $date->lte($previous_end_date); $date->addDay()) {
            // Store the count of records for the current date
            $previous_data = $this->calculatePresent($all_manager_user_ids, $date, $data_query);
            $data["last_amount"] = $data["last_amount"] + $previous_data;

        }

        return $data;

      }
    public function present(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids


    ) {

        $all_manager_user_ids = $this->get_all_user_of_manager($all_manager_department_ids);


        $data_query  = Attendance::where([
            "is_present" => 1
        ]);


        $data["today"] = $this->calculatePresent($all_manager_user_ids, $today, $data_query);

        $data["monthly"] = $this->getPresentData(
            $all_manager_user_ids,
            $data_query,
            [
                "start_date" => $start_date_of_this_week,
                "end_date" => $end_date_of_this_week,
                "previous_start_date" => $start_date_of_previous_week,
                "previous_end_date" => $end_date_of_previous_week,
            ]
    );

    $data["weekly"] = $this->getPresentData(
        $all_manager_user_ids,
        $data_query,
        [
            "start_date" => $start_date_of_this_week,
            "end_date" => $end_date_of_this_week,
            "previous_start_date" => $start_date_of_previous_week,
            "previous_end_date" => $end_date_of_previous_week,
        ]
);


        return $data;
    }


      /**
     *
     * @OA\Get(
     *      path="/v2.0/business-manager-dashboard",
     *      operationId="getBusinessManagerDashboardData",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
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

     public function getBusinessManagerDashboardData(Request $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");

             $business_id = auth()->user()->business_id;


             if (!$business_id) {
                 return response()->json([
                     "message" => "You are not a business user"
                 ], 401);
             }
             $today = today();



             $start_date_of_next_month = Carbon::now()->startOfMonth()->addMonth(1);
             $end_date_of_next_month = Carbon::now()->endOfMonth()->addMonth(1);
             $start_date_of_this_month = Carbon::now()->startOfMonth();
             $end_date_of_this_month = Carbon::now()->endOfMonth();
             $start_date_of_previous_month = Carbon::now()->startOfMonth()->subMonth(1);
             $end_date_of_previous_month = Carbon::now()->startOfMonth()->subDay(1);

             $start_date_of_next_week = Carbon::now()->startOfWeek()->addWeek(1);
             $end_date_of_next_week = Carbon::now()->endOfWeek()->addWeek(1);
             $start_date_of_this_week = Carbon::now()->startOfWeek();
             $end_date_of_this_week = Carbon::now()->endOfWeek();
             $start_date_of_previous_week = Carbon::now()->startOfWeek()->subWeek(1);
             $end_date_of_previous_week = Carbon::now()->endOfWeek()->subWeek(1);



             $all_manager_department_ids = $this->get_all_departments_of_manager();




             $data["total_employee"] = $this->total_employee(
                 $today,
                 $start_date_of_next_month,
                 $end_date_of_next_month,
                 $start_date_of_this_month,
                 $end_date_of_this_month,
                 $start_date_of_previous_month,
                 $end_date_of_previous_month,
                 $start_date_of_next_week,
                 $end_date_of_next_week,
                 $start_date_of_this_week,
                 $end_date_of_this_week,
                 $start_date_of_previous_week,
                 $end_date_of_previous_week,
                 $all_manager_department_ids
             );





             $data["open_roles"] = $this->open_roles(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids
            );



            $data["absent"] = $this->absent(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids,

            );


            $data["present"] = $this->present(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids,

            );




        return response()->json($data, 200);

             $data["employee_on_holiday"] = $this->employee_on_holiday(
                 $today,
                 $start_date_of_next_month,
                 $end_date_of_next_month,
                 $start_date_of_this_month,
                 $end_date_of_this_month,
                 $start_date_of_previous_month,
                 $end_date_of_previous_month,
                 $start_date_of_next_week,
                 $end_date_of_next_week,
                 $start_date_of_this_week,
                 $end_date_of_this_week,
                 $start_date_of_previous_week,
                 $end_date_of_previous_week,
                 $all_manager_department_ids,

             );



             $data["employee_on_holiday"]["id"] = 2;


             $data["employee_on_holiday"]["widget_name"] = "employee_on_holiday";
             $data["employee_on_holiday"]["widget_type"] = "default";
             $data["employee_on_holiday"]["route"] =  '/employee/all-employees?is_on_holiday=1&';

             $start_id = 3;
             $leave_statuses = ['pending_approval','in_progress', 'approved','rejected'];

             foreach ($leave_statuses as $leave_status) {
                 $data[($leave_status . "_leaves")] = $this->leaves(
                     $today,
                     $start_date_of_next_month,
                     $end_date_of_next_month,
                     $start_date_of_this_month,
                     $end_date_of_this_month,
                     $start_date_of_previous_month,
                     $end_date_of_previous_month,
                     $start_date_of_next_week,
                     $end_date_of_next_week,
                     $start_date_of_this_week,
                     $end_date_of_this_week,
                     $start_date_of_previous_week,
                     $end_date_of_previous_week,
                     $all_manager_department_ids,
                     $leave_status
                 );




                 $data[($leave_status . "_leaves")]["id"] = $start_id++;




                 $data[($leave_status . "_leaves")]["widget_name"] = ($leave_status . "_leaves");

                 $data[($leave_status . "_leaves")]["widget_type"] = "default";


                 $data[($leave_status . "_leaves")]["route"] = ('/leave/leaves?status=' . $leave_status . "&");
             }








             $data["upcoming_passport_expiries"] = $this->upcoming_passport_expiries(
                 $today,
                 $start_date_of_next_month,
                 $end_date_of_next_month,
                 $start_date_of_this_month,
                 $end_date_of_this_month,
                 $start_date_of_previous_month,
                 $end_date_of_previous_month,
                 $start_date_of_next_week,
                 $end_date_of_next_week,
                 $start_date_of_this_week,
                 $end_date_of_this_week,
                 $start_date_of_previous_week,
                 $end_date_of_previous_week,
                 $all_manager_department_ids
             );



             $data["upcoming_passport_expiries"]["id"] =$start_id++;






             $data["upcoming_passport_expiries"]["widget_name"] = "upcoming_passport_expiries";
             $data["upcoming_passport_expiries"]["widget_type"] = "multiple_upcoming_days";
             $data["upcoming_passport_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=passport&";

             $data["upcoming_visa_expiries"] = $this->upcoming_visa_expiries(
                 $today,
                 $start_date_of_next_month,
                 $end_date_of_next_month,
                 $start_date_of_this_month,
                 $end_date_of_this_month,
                 $start_date_of_previous_month,
                 $end_date_of_previous_month,
                 $start_date_of_next_week,
                 $end_date_of_next_week,
                 $start_date_of_this_week,
                 $end_date_of_this_week,
                 $start_date_of_previous_week,
                 $end_date_of_previous_week,
                 $all_manager_department_ids
             );



             $data["upcoming_visa_expiries"]["id"] = $start_id++;



             $data["upcoming_visa_expiries"]["widget_name"] = "upcoming_visa_expiries";
             $data["upcoming_visa_expiries"]["widget_type"] = "multiple_upcoming_days";


             $data["upcoming_visa_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=visa&";



             $data["upcoming_right_to_work_expiries"] = $this->upcoming_right_to_work_expiries(
                 $today,
                 $start_date_of_next_month,
                 $end_date_of_next_month,
                 $start_date_of_this_month,
                 $end_date_of_this_month,
                 $start_date_of_previous_month,
                 $end_date_of_previous_month,
                 $start_date_of_next_week,
                 $end_date_of_next_week,
                 $start_date_of_this_week,
                 $end_date_of_this_week,
                 $start_date_of_previous_week,
                 $end_date_of_previous_week,
                 $all_manager_department_ids
             );



             $data["upcoming_right_to_work_expiries"]["id"] = $start_id++;





             $data["upcoming_right_to_work_expiries"]["widget_name"] = "upcoming_right_to_work_expiries";
             $data["upcoming_right_to_work_expiries"]["widget_type"] = "multiple_upcoming_days";
             $data["upcoming_right_to_work_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=right_to_work&";











             $data["upcoming_sponsorship_expiries"] = $this->upcoming_sponsorship_expiries(
                 $today,
                 $start_date_of_next_month,
                 $end_date_of_next_month,
                 $start_date_of_this_month,
                 $end_date_of_this_month,
                 $start_date_of_previous_month,
                 $end_date_of_previous_month,
                 $start_date_of_next_week,
                 $end_date_of_next_week,
                 $start_date_of_this_week,
                 $end_date_of_this_week,
                 $start_date_of_previous_week,
                 $end_date_of_previous_week,
                 $all_manager_department_ids
             );




             $data["upcoming_sponsorship_expiries"]["id"] = $start_id++;




             $data["upcoming_sponsorship_expiries"]["widget_name"] = "upcoming_sponsorship_expiries";
             $data["upcoming_sponsorship_expiries"]["widget_type"] = "multiple_upcoming_days";
             $data["upcoming_sponsorship_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=sponsorship&";





             $sponsorship_statuses = ['unassigned', 'assigned', 'visa_applied','visa_rejected','visa_grantes','withdrawal'];
             foreach ($sponsorship_statuses as $sponsorship_status) {
                 $data[($sponsorship_status . "_sponsorships")] = $this->sponsorships(
                     $today,
                     $start_date_of_next_month,
                     $end_date_of_next_month,
                     $start_date_of_this_month,
                     $end_date_of_this_month,
                     $start_date_of_previous_month,
                     $end_date_of_previous_month,
                     $start_date_of_next_week,
                     $end_date_of_next_week,
                     $start_date_of_this_week,
                     $end_date_of_this_week,
                     $start_date_of_previous_week,
                     $end_date_of_previous_week,
                     $all_manager_department_ids,
                     $sponsorship_status
                 );



                 $data[($sponsorship_status . "_sponsorships")]["id"] = $start_id++;




                 $data[($sponsorship_status . "_sponsorships")]["widget_name"] = ($sponsorship_status . "_sponsorships");

                 $data[($sponsorship_status . "_sponsorships")]["widget_type"] = "default";
                 $data[($sponsorship_status . "_sponsorships")]["route"] = '/employee/all-employees?sponsorship_status=' . $sponsorship_status . "&";

             }









             $data["upcoming_pension_expiries"] = $this->upcoming_pension_expiries(
                 $today,
                 $start_date_of_next_month,
                 $end_date_of_next_month,
                 $start_date_of_this_month,
                 $end_date_of_this_month,
                 $start_date_of_previous_month,
                 $end_date_of_previous_month,
                 $start_date_of_next_week,
                 $end_date_of_next_week,
                 $start_date_of_this_week,
                 $end_date_of_this_week,
                 $start_date_of_previous_week,
                 $end_date_of_previous_week,
                 $all_manager_department_ids,

             );





             $data["upcoming_pension_expiries"]["id"] = $start_id++;





             $data["upcoming_pension_expiries"]["widget_name"] = "upcoming_pension_expiries";

             $data["upcoming_pension_expiries"]["widget_type"] = "multiple_upcoming_days";

             $data["upcoming_pension_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=pension&";


             $pension_statuses = ["opt_in", "opt_out"];
             foreach ($pension_statuses as $pension_status) {
                 $data[($pension_status . "_pensions")] = $this->pensions(
                     $today,
                     $start_date_of_next_month,
                     $end_date_of_next_month,
                     $start_date_of_this_month,
                     $end_date_of_this_month,
                     $start_date_of_previous_month,
                     $end_date_of_previous_month,
                     $start_date_of_next_week,
                     $end_date_of_next_week,
                     $start_date_of_this_week,
                     $end_date_of_this_week,
                     $start_date_of_previous_week,
                     $end_date_of_previous_week,
                     $all_manager_department_ids,
                     "pension_scheme_status",
                     $pension_status
                 );



                 $data[($pension_status . "_pensions")]["id"] = $start_id++;





                 $data[($pension_status . "_pensions")]["widget_name"] = ($pension_status . "_pensions");
                 $data[($pension_status . "_pensions")]["widget_type"] = "default";
                 $data[($pension_status . "_pensions")]["route"] = '/employee/all-employees?pension_scheme_status=' . $pension_status . "&";
             }


             $employment_statuses = $this->getEmploymentStatuses();

             foreach ($employment_statuses as $employment_status) {
                 $data["emplooyment_status_wise"]["data"][($employment_status->name . "_employees")] = $this->employees_by_employment_status(
                     $today,
                     $start_date_of_next_month,
                     $end_date_of_next_month,
                     $start_date_of_this_month,
                     $end_date_of_this_month,
                     $start_date_of_previous_month,
                     $end_date_of_previous_month,
                     $start_date_of_next_week,
                     $end_date_of_next_week,
                     $start_date_of_this_week,
                     $end_date_of_this_week,
                     $start_date_of_previous_week,
                     $end_date_of_previous_week,
                     $all_manager_department_ids,
                     $employment_status->id
                 );



                 $data["emplooyment_status_wise"]["id"] = $start_id++;





                 $data["emplooyment_status_wise"]["widget_name"] = "employment_status_wise_employee";
                 $data["emplooyment_status_wise"]["widget_type"] = "graph";

                 $data["emplooyment_status_wise"]["route"] = ('/employee/?status=' . $employment_status->name . "&");
             }



























             $data["present_today"] = $this->present_today(
                 $today,
                 $start_date_of_next_month,
                 $end_date_of_next_month,
                 $start_date_of_this_month,
                 $end_date_of_this_month,
                 $start_date_of_previous_month,
                 $end_date_of_previous_month,
                 $start_date_of_next_week,
                 $end_date_of_next_week,
                 $start_date_of_this_week,
                 $end_date_of_this_week,
                 $start_date_of_previous_week,
                 $end_date_of_previous_week,
                 $all_manager_department_ids,

             );





             $data["present_today"]["id"] = $start_id++;




             $data["present_today"]["widget_name"] = "present_today";
             $data["present_today"]["widget_type"] = "default";
             $data["present_today"]["route"] = "/employee/all-employees?present_today=1&";


             return response()->json($data, 200);
         } catch (Exception $e) {
             return $this->sendError($e, 500, $request);
         }
     }

































}
