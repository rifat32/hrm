<?php

namespace App\Http\Controllers;

use App\Http\Utils\AttendanceUtil;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\EmployeePassportDetailHistory;
use App\Models\EmployeePensionHistory;
use App\Models\EmployeeRightToWorkHistory;
use App\Models\EmployeeSponsorshipHistory;
use App\Models\EmployeeVisaDetailHistory;
use App\Models\EmploymentStatus;
use App\Models\JobListing;
use App\Models\LeaveRecord;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class DashboardManagementControllerV2 extends Controller
{
    use ErrorUtil, BusinessUtil, UserActivityUtil, BasicUtil, AttendanceUtil;


//   function getLast12MonthsDates() {
//     $dates = [];
//     $currentDate = Carbon::now();

//     // Start from the previous month to avoid adding the current month twice
//     $currentDate->subMonth();

//     for ($i = 0; $i < 12; $i++) {
//         $startOfMonth = $currentDate->copy()->startOfMonth()->toDateString();
//         $endOfMonth = $currentDate->copy()->endOfMonth()->toDateString();
//         $monthName = $currentDate->copy()->format('F');

//         $dates[] = [
//             'month' => $monthName,
//             'start_date' => $startOfMonth,
//             'end_date' => $endOfMonth,
//         ];

//         // Move to the previous month
//         $currentDate->subMonth();
//     }

//     return $dates;
// }

function getLast12MonthsDates() {
    $dates = [];
    $currentDate = Carbon::now();

    // Get the current year
    $year = $currentDate->year;

    for ($month = 1; $month <= 12; $month++) {
        // Create a date object for the first day of the current month
        $date = Carbon::createFromDate($year, $month, 1);

        $startOfMonth = $date->copy()->startOfMonth()->toDateString();
        $endOfMonth = $date->copy()->endOfMonth()->toDateString();
        $monthName = $date->copy()->format('F');

        $dates[] = [
            'month' => substr($monthName, 0, 3),
            'start_date' => $startOfMonth,
            'end_date' => $endOfMonth,
        ];
    }

    return $dates;
}

    public function getLeaveData($data_query, $start_date = "",$end_date = "") {
        $updated_data_query_old = clone $data_query;
        $updated_data_query = $updated_data_query_old->when(
            (!empty($start_date) && !empty($end_date)),
            function($query) use($start_date, $end_date) {
                $query->whereBetween("leave_records.date", [$start_date, $end_date . ' 23:59:59']);
            }
        );

    $data["total_requested"] = clone $updated_data_query;
        $data["total_requested"] = $data["total_requested"]
        ->count();




        $data["total_pending"] = clone $updated_data_query;
        $data["total_pending"] = $data["total_pending"]
        ->whereHas("leave",function($query) {
            $query->where([
                "leaves.status" => "pending_approval"
            ]);
        })
        ->count();

        $data["total_approved"] = clone $updated_data_query;
        $data["total_approved"] = $data["total_approved"]
        ->whereHas("leave",function($query) {
            $query->where([
                "leaves.status" => "approved"
            ]);
        })
       ->count();

        $data["total_rejected"] = clone $updated_data_query;
        $data["total_rejected"] = $data["total_rejected"]
        ->whereHas("leave",function($query) {
            $query->where([
                "leaves.status" => "rejected"
            ]);
        })


        ->count();




        return $data;
    }
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
        $show_my_data = false
    ) {

        $data_query  = LeaveRecord::whereHas("leave", function ($query)  {
                $query->where([
                    "leaves.business_id" => auth()->user()->business_id,
                ]);
            });


$data["individual_total"] = $this->getLeaveData($data_query);

$last12MonthsDates = $this->getLast12MonthsDates();

foreach ($last12MonthsDates as $month) {
    $leaveData =  $this->getLeaveData($data_query,$month['start_date'],$month['end_date']);
    $data["data"][] = array_merge(
        ["month" => $month['month']],
        $leaveData
    );
}






        return $data;
    }


    public function getData($data_query,$dateField,$dates) {

        $data["current_amount"] = clone $data_query;
        $data["current_amount"] = $data["current_amount"]->whereBetween($dateField, [$dates["start_date"], ($dates["end_date"] . ' 23:59:59')])->count();



        $data["last_amount"] = clone $data_query;
        $data["last_amount"] = $data["last_amount"]->whereBetween($dateField, [$dates["previous_start_date"], ($dates["previous_end_date"] . ' 23:59:59')])->count();



        $all_data = clone $data_query;
        $all_data = $all_data->whereBetween($dateField, [$dates["start_date"], ($dates["end_date"] . ' 23:59:59')])->get();

$start_date = Carbon::parse($dates["start_date"]);
$end_date = Carbon::parse(($dates["end_date"]));
// Initialize an array to hold the counts for each date
$data["data"] = [];

// Loop through each day in the date range
for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
    // Filter the data for the current date
    $filtered_data = $all_data->filter(function ($item) use ($date) {
        return Carbon::parse($item->created_at)->isSameDay($date);
    });

    // Store the count of records for the current date
    $data["data"][] = [
      "date" => $date->toDateString(),
      "total" => $filtered_data->count()
    ];




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

        $data_query  = User::whereHas("department_user.department", function ($query) use ($all_manager_department_ids) {
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
            "posted_on",
            [
                "start_date" => $start_date_of_this_month,
                "end_date" => $end_date_of_this_month,
                "previous_start_date" => $start_date_of_previous_month,
                "previous_end_date" => $end_date_of_previous_month,
            ]
    );

    $data["weekly"] = $this->getData(
        $data_query,
        "posted_on",
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
    $end_date = Carbon::parse(($dates["end_date"]));
    // Loop through each day in the date range
    for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {


     $absent_count = $this->calculateAbsent($all_manager_user_ids, $date, $data_query);


        $data["data"][] = [
            "date" => $date->toDateString(),
            "total" => $absent_count
        ];



        $data["current_amount"] = $data["current_amount"] + $absent_count;













    }


$previous_start_date = Carbon::parse($dates["previous_start_date"]);
$previous_end_date = Carbon::parse(($dates["previous_end_date"]));

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
        $end_date = Carbon::parse(($dates["end_date"]));
        // Loop through each day in the date range
        for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {


            $present_count = $this->calculatePresent($all_manager_user_ids, $date, $data_query);
            $data["data"][] = [
                "date" => $date->toDateString(),
                "total" => $present_count
            ];

            $data["current_amount"] = $data["current_amount"] + $present_count;
        }


    $previous_start_date = Carbon::parse($dates["previous_start_date"]);
    $previous_end_date = Carbon::parse(($dates["previous_end_date"]));

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

    public function employee_on_holiday(
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
        $total_departments = Department::where([
            "business_id" => auth()->user()->business_id,
            "is_active" => 1
        ])->count();

        $data_query  = User::whereHas("department_user.department", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->whereNotIn('id', [auth()->user()->id])

            // ->where('is_in_employee', 1)

            ->where('is_active', 1)
            ->where("business_id",auth()->user()->id);


        // $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]

        ->where(function($query) use ($today, $total_departments)  {
                 $query->where(function($query) use ($today, $total_departments) {

                    $query->where(function($query) use ($today,$total_departments) {
                        $query->whereHas('holidays', function ($query) use ($today) {
                            $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay())
                            ->where('holidays.end_date', ">=",  $today->copy()->endOfDay());

                        })
                        ->orWhere(function($query) use($today, $total_departments) {
                              $query->whereHasRecursiveHolidays($today,$total_departments);
                        });

                        // ->whereHas('departments.holidays', function ($query) use ($today) {
                        //     $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay())
                        //     ->where('holidays.end_date', ">=",  $today->copy()->endOfDay());
                        // });

                    })
                    ->where(function($query) use ($today) {
                        $query->orWhereDoesntHave('holidays', function ($query) use ($today) {
                            $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay())
                                  ->where('holidays.end_date', ">=",  $today->copy()->endOfDay())
                                  ->orWhere(function ($query) {
                                    $query->whereDoesntHave("users")
                                        ->whereDoesntHave("departments");
                                });


                        });
                    });





                })
                ->orWhere(
                    function($query) use ($today) {
                    $query->orWhereDoesntHave('holidays', function ($query) use ($today) {
                        $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay());
                        $query->where('holidays.end_date', ">=",  $today->copy()->endOfDay());
                        $query->doesntHave('users');

                    });

                }
            );
        })



       ->count();

        // $data["next_week_data_count"] = clone $data_query;
        // $data["next_week_data_count"] = $data["next_week_data_count"]

        // ->where(function($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //     $query->whereHas('departments.holidays', function ($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_week . ' 23:59:59');
        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_week . ' 23:59:59');
        //         $query->doesntHave('departments');

        //     });
        // })
        // ->orWhere(function($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //     $query->whereHas('holidays', function ($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_week . ' 23:59:59');
        //     })->orWhereDoesntHave('holidays', function ($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_week . ' 23:59:59');
        //         $query->doesntHave('users');

        //     });
        // })

        // ->count();

        // $data["this_week_data_count"] = clone $data_query;
        // $data["this_week_data_count"] = $data["this_week_data_count"]

        // ->where(function($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //     $query->whereHas('departments.holidays', function ($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_week . ' 23:59:59');
        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_week . ' 23:59:59');
        //         $query->doesntHave('departments');
        //     });
        // })

        // ->orWhere(function($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //     $query->whereHas('holidays', function ($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_week . ' 23:59:59');
        //     })->orWhereDoesntHave('holidays', function ($query) use ( $start_date_of_this_week,$end_date_of_this_week) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_week . ' 23:59:59');
        //         $query->doesntHave('users');


        //     });
        // })



        // ->count();

        // $data["previous_week_data_count"] = clone $data_query;
        // $data["previous_week_data_count"] = $data["previous_week_data_count"]
        // ->where(function($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {
        //     $query->whereHas('departments.holidays', function ($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_week . ' 23:59:59');
        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_week . ' 23:59:59');
        //         $query->doesntHave('departments');

        //     });
        // })
        // ->orWhere(function($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {
        //     $query->whereHas('holidays', function ($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_week . ' 23:59:59');
        //     })->orWhereDoesntHave('holidays', function ($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_week . ' 23:59:59');
        //         $query->doesntHave('users');

        //     });
        // })



        // ->count();

        // $data["next_month_data_count"] = clone $data_query;
        // $data["next_month_data_count"] = $data["next_month_data_count"]
        // ->where(function($query) use ($start_date_of_next_month,$end_date_of_next_month) {
        //     $query->whereHas('departments.holidays', function ($query) use ( $start_date_of_next_month,$end_date_of_next_month) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_month . ' 23:59:59');
        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ($start_date_of_next_month,$end_date_of_next_month) {

        //          $query->where('holidays.start_date', "<=",  $start_date_of_next_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_month . ' 23:59:59');
        //         $query->doesntHave('departments');


        //     });
        // })
        // ->orWhere(function($query) use ($start_date_of_next_month,$end_date_of_next_month) {
        //     $query->whereHas('holidays', function ($query) use ( $start_date_of_next_month,$end_date_of_next_month) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_month . ' 23:59:59');
        //     })->orWhereDoesntHave('holidays', function ($query) use ($start_date_of_next_month,$end_date_of_next_month) {

        //          $query->where('holidays.start_date', "<=",  $start_date_of_next_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_month . ' 23:59:59');
        //         $query->doesntHave('users');


        //     });
        // })


        // ->count();

        // $data["this_month_data_count"] = clone $data_query;
        // $data["this_month_data_count"] = $data["this_month_data_count"]
        // ->where(function($query) use ( $start_date_of_this_month,$end_date_of_this_month) {
        //     $query->whereHas('departments.holidays', function ($query) use ( $start_date_of_this_month,$end_date_of_this_month) {


        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_month . ' 23:59:59');


        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ( $start_date_of_this_month,$end_date_of_this_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_month . ' 23:59:59');
        //         $query->doesntHave('departments');


        //     });

        // })

        // ->orWhere(function($query) use ( $start_date_of_this_month,$end_date_of_this_month) {
        //     $query->whereHas('holidays', function ($query) use ( $start_date_of_this_month,$end_date_of_this_month) {


        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_month . ' 23:59:59');


        //     })->orWhereDoesntHave('holidays', function ($query) use ( $start_date_of_this_month,$end_date_of_this_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_month . ' 23:59:59');
        //         $query->doesntHave('users');


        //     });

        // })

        // ->count();


        // $data["previous_month_data_count"] = clone $data_query;
        // $data["previous_month_data_count"] = $data["previous_month_data_count"]

        // ->where(function($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {
        //     $query ->whereHas('departments.holidays', function ($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_month . ' 23:59:59');


        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_month . ' 23:59:59');
        //         $query->doesntHave('departments');

        //     });



        // })

        // ->orWhere(function($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {
        //     $query ->whereHas('holidays', function ($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_month . ' 23:59:59');




        //     })->orWhereDoesntHave('holidays', function ($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_month . ' 23:59:59');
        //         $query->doesntHave('users');

        //     });



        // })




        // ->count();


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
    public function upcoming_passport_expiries(
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

        $issue_date_column = 'passport_issue_date';
        $expiry_date_column = 'passport_expiry_date';


        $employee_passport_history_ids = EmployeePassportDetailHistory::select('user_id')
        ->where("business_id",auth()->user()->business_id)
        ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereNotIn('user_id', [auth()->user()->id])
        ->where($issue_date_column, '<', now())
        ->groupBy('user_id')
        ->get()
        ->map(function ($record) use ($issue_date_column, $expiry_date_column) {

            $latest_expired_record = EmployeePassportDetailHistory::where('user_id', $record->user_id)
            ->where($issue_date_column, '<', now())
            ->orderByDesc($expiry_date_column)
            // ->latest()
            ->first();

            if($latest_expired_record) {
                 $current_data = EmployeePassportDetailHistory::where('user_id', $record->user_id)
                ->where($expiry_date_column, $latest_expired_record[$expiry_date_column])
                ->where($issue_date_column, '<', now())
                ->orderByDesc($issue_date_column)
                ->first();
            } else {
               return NULL;
            }


                return $current_data?$current_data->id:NULL;
        })
        ->filter()->values();



        $data_query  = EmployeePassportDetailHistory::whereIn('id', $employee_passport_history_ids)
        ->where($expiry_date_column,">=", today());



        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('passport_expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
$data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('passport_expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('passport_expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('passport_expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('passport_expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('passport_expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween('passport_expiry_date', [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }
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
    public function upcoming_visa_expiries(
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

        $issue_date_column = 'visa_issue_date';
        $expiry_date_column = 'visa_expiry_date';


        $employee_visa_history_ids = EmployeeVisaDetailHistory::select('user_id')
        ->where("business_id",auth()->user()->business_id)
        ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereNotIn('user_id', [auth()->user()->id])
        ->where($issue_date_column, '<', now())
        ->groupBy('user_id')
        ->get()
        ->map(function ($record) use ($issue_date_column, $expiry_date_column) {

            $latest_expired_record = EmployeeVisaDetailHistory::where('user_id', $record->user_id)
            ->where($issue_date_column, '<', now())
            ->orderByDesc($expiry_date_column)
            // ->latest()
            ->first();

            if($latest_expired_record) {
                 $current_data = EmployeeVisaDetailHistory::where('user_id', $record->user_id)
                ->where($expiry_date_column, $latest_expired_record[$expiry_date_column])
                ->where($issue_date_column, '<', now())
                ->orderByDesc($issue_date_column)
                ->first();
            } else {
               return NULL;
            }


                return $current_data?$current_data->id:NULL;
        })
        ->filter()->values();




        $data_query  = EmployeeVisaDetailHistory::whereIn('id', $employee_visa_history_ids)
        ->where($expiry_date_column,">=", today());

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('visa_expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('visa_expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('visa_expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('visa_expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();


        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('visa_expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('visa_expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween('visa_expiry_date', [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }

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

    public function upcoming_right_to_work_expiries(
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

        $issue_date_column = 'right_to_work_check_date';
        $expiry_date_column = 'right_to_work_expiry_date';


        $employee_right_to_work_history_ids = EmployeeRightToWorkHistory::select('user_id')
        ->where("business_id",auth()->user()->business_id)
        ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereNotIn('user_id', [auth()->user()->id])
        ->where($issue_date_column, '<', now())
        ->groupBy('user_id')
        ->get()
        ->map(function ($record) use ($issue_date_column, $expiry_date_column) {

            $latest_expired_record = EmployeeRightToWorkHistory::where('user_id', $record->user_id)
            ->where($issue_date_column, '<', now())
            ->orderByDesc($expiry_date_column)
            // ->latest()
            ->first();

            if($latest_expired_record) {
                 $current_data = EmployeeRightToWorkHistory::where('user_id', $record->user_id)
                ->where($expiry_date_column, $latest_expired_record[$expiry_date_column])
                ->where($issue_date_column, '<', now())
                ->orderByDesc($issue_date_column)
                ->first();
            } else {
               return NULL;
            }


            return $current_data?$current_data->id:NULL;
        })
        ->filter()->values();



        $data_query  = EmployeeRightToWorkHistory::whereIn('id', $employee_right_to_work_history_ids)
        ->where($expiry_date_column,">=", today());


        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('right_to_work_expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('right_to_work_expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('right_to_work_expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('right_to_work_expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('right_to_work_expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('right_to_work_expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween('right_to_work_expiry_date', [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }

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

    public function upcoming_sponsorship_expiries(
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

        $issue_date_column = 'date_assigned';
        $expiry_date_column = 'expiry_date';


        $employee_sponsorship_history_ids = EmployeeSponsorshipHistory::select('user_id')
        ->where("business_id",auth()->user()->business_id)
        ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereNotIn('user_id', [auth()->user()->id])
        ->where($issue_date_column, '<', now())
        ->groupBy('user_id')
        ->get()
        ->map(function ($record) use ($issue_date_column, $expiry_date_column) {
            $latest_expired_record = EmployeeSponsorshipHistory::where('user_id', $record->user_id)
            ->where($issue_date_column, '<', now())
            ->orderByDesc($expiry_date_column)
            // ->latest()
            ->first();

            if($latest_expired_record) {
                 $current_data = EmployeeSponsorshipHistory::where('user_id', $record->user_id)
                ->where($expiry_date_column, $latest_expired_record[$expiry_date_column])
                ->where($issue_date_column, '<', now())
                ->orderByDesc($issue_date_column)
                ->first();
            } else {
               return NULL;
            }
            return $current_data?$current_data->id:NULL;
        })
        ->filter()->values();



        $data_query  = EmployeeSponsorshipHistory::whereIn('id', $employee_sponsorship_history_ids)
        ->where($expiry_date_column,">=", today());


        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween('expiry_date', [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }


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

    public function sponsorships(
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
        $current_certificate_status
    ) {


        $issue_date_column = 'date_assigned';
        $expiry_date_column = 'expiry_date';

        $employee_sponsorship_history_ids = EmployeeSponsorshipHistory::select('user_id')
        ->where("business_id",auth()->user()->business_id)
        ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereNotIn('user_id', [auth()->user()->id])
        ->where($issue_date_column, '<', now())
        ->groupBy('user_id')
        ->get()
        ->map(function ($record) use ($issue_date_column, $expiry_date_column) {
            $latest_expired_record = EmployeeSponsorshipHistory::where('user_id', $record->user_id)
            ->where($issue_date_column, '<', now())
            ->orderByDesc($expiry_date_column)
            // ->latest()
            ->first();

            if($latest_expired_record) {
                 $current_data = EmployeeSponsorshipHistory::where('user_id', $record->user_id)
                ->where($expiry_date_column, $latest_expired_record[$expiry_date_column])
                ->where($issue_date_column, '<', now())
                ->orderByDesc($issue_date_column)
                ->first();
            } else {
               return NULL;
            }
            return $current_data?$current_data->id:NULL;
        })
        ->filter()->values();



        $data_query  = EmployeeSponsorshipHistory::whereIn('id', $employee_sponsorship_history_ids)
        ->where([
            "current_certificate_status"=>$current_certificate_status,
            "business_id"=>auth()->user()->business_id
        ]);




        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();

        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween('expiry_date', [$start_date_of_previous_week, ($end_date_of_previous_week . ' 23:59:59')])->count();

        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();

        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween('expiry_date', [$start_date_of_previous_month, ($end_date_of_previous_month . ' 23:59:59')])->count();
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


    public function upcoming_pension_expiries(
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


        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';


        $employee_pension_history_ids = EmployeePensionHistory::select('id','user_id')
        ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
            $query->where("users.pension_eligible",">",0);
        })
        ->whereNotIn('user_id', [auth()->user()->id])
        ->where($issue_date_column, '<', now())
        ->whereNotNull($expiry_date_column)
        ->groupBy('user_id')
        ->get()
        ->map(function ($record) use ($issue_date_column, $expiry_date_column) {


            $current_data = EmployeePensionHistory::where('user_id', $record->user_id)
            ->where("pension_eligible", 1)
            ->where($issue_date_column, '<', now())
                ->orderByDesc("id")
                ->first();

                if(empty($current_data))
                {
                    return NULL;
                }


                return $current_data->id;
        })
        ->filter()->values();

        $data_query  = EmployeePensionHistory::whereIn('id', $employee_pension_history_ids)->where($expiry_date_column,">=", today());














        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween($expiry_date_column, [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween($expiry_date_column, [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween($expiry_date_column, [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }


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
    public function pensions(
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
        $status_column,
        $status_value
    ) {


        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';


        $employee_pension_history_ids = EmployeePensionHistory::select('user_id')
        ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereHas("employee", function ($query)  {
            $query->where("users.pension_eligible",">",0)
            ->where("is_active",1);
        })
        ->whereNotIn('user_id', [auth()->user()->id])
        ->where($issue_date_column, '<', now())
        ->whereNotNull($expiry_date_column)
        ->groupBy('user_id')
        ->get()
        ->map(function ($record) use ($issue_date_column, $expiry_date_column) {

            // $latest_expired_record = EmployeePensionHistory::where('user_id', $record->user_id)
            // ->where($issue_date_column, '<', now())
            // ->where(function($query) use($expiry_date_column) {
            //    $query->whereNotNull($expiry_date_column)
            //    ->orWhereNull($expiry_date_column);
            // })
            // ->orderByRaw("ISNULL($expiry_date_column), $expiry_date_column DESC")
            // ->orderBy('id', 'DESC')
            // // ->orderByDesc($expiry_date_column)
            // // ->latest()
            // ->first();

            // if($latest_expired_record->expiry_date_column) {
            //      $current_data = EmployeePensionHistory::where('user_id', $record->user_id)
            //     ->where($expiry_date_column, $latest_expired_record->expiry_date_column)
            //     ->orderByDesc($issue_date_column)
            //     ->first();
            // } else {
            //    return NULL;
            // }

     $current_data = EmployeePensionHistory::where('user_id', $record->user_id)
            ->where("pension_eligible", 1)
            ->where($issue_date_column, '<', now())
                ->orderByDesc("id")
                ->first();

                if($current_data)
                {
                    return NULL;
                }



                return $current_data->id;
        })
        ->filter()->values();




        $data_query  = EmployeePensionHistory::whereIn('id', $employee_pension_history_ids)
        ->when(!empty($status_column), function($query) use ($status_column,$status_value) {
         $query->where($status_column, $status_value);
        });





        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween($expiry_date_column, [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();

        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_previous_week, ($end_date_of_previous_week . ' 23:59:59')])->count();

        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();

        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_previous_month, ($end_date_of_previous_month . ' 23:59:59')])->count();

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


    public function getEmploymentStatuses () {
        $created_by  = NULL;
        if(auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }
        $employmentStatuses = EmploymentStatus::
        when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
            if (auth()->user()->hasRole('superadmin')) {
                return $query->where('employment_statuses.business_id', NULL)
                    ->where('employment_statuses.is_default', 1)
                    ->where('employment_statuses.is_active', 1);

            } else {
                return $query->where('employment_statuses.business_id', NULL)
                    ->where('employment_statuses.is_default', 1)
                    ->where('employment_statuses.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) {
                        $q->whereIn("disabled_employment_statuses.created_by", [auth()->user()->id]);
                    })

                    ->orWhere(function ($query)   {
                        $query->where('employment_statuses.business_id', NULL)
                            ->where('employment_statuses.is_default', 0)
                            ->where('employment_statuses.created_by', auth()->user()->id)
                            ->where('employment_statuses.is_active', 1);


                    });
            }
        })
            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by) {
                return $query->where('employment_statuses.business_id', NULL)
                    ->where('employment_statuses.is_default', 1)
                    ->where('employment_statuses.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                        $q->whereIn("disabled_employment_statuses.created_by", [$created_by]);
                    })
                    ->whereDoesntHave("disabled", function($q)  {
                        $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                    })

                    ->orWhere(function ($query) use( $created_by){
                        $query->where('employment_statuses.business_id', NULL)
                            ->where('employment_statuses.is_default', 0)
                            ->where('employment_statuses.created_by', $created_by)
                            ->where('employment_statuses.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) {
                                $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                            });
                    })
                    ->orWhere(function ($query)   {
                        $query->where('employment_statuses.business_id', auth()->user()->business_id)
                            ->where('employment_statuses.is_default', 0)
                            ->where('employment_statuses.is_active', 1);

                    });
            })->get();

            return $employmentStatuses;
    }

    public function employees_by_employment_status(
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
        $employment_status_id
    ) {

        $data_query  = User::whereHas("department_user.department", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->whereNotIn('id', [auth()->user()->id])
            ->where([
                 "employment_status_id" => $employment_status_id
            ])
            // ->where('is_in_employee', 1)
            // ->where('is_active', 1)
            ;
            $data["total_data"] = $data_query->get();

            $data["total_data_count"] = $data_query->count();

            $data["today_data_count"] = clone $data_query;
            $data["today_data_count"] = $data["today_data_count"]->whereBetween('users.created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();


            $data["yesterday_data_count"] = clone $data_query;
            $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('users.created_at', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();



            $data["this_week_data_count"] = clone $data_query;
            $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('created_at', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();




            $data["previous_week_data_count"] = clone $data_query;
            $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween('created_at', [$start_date_of_previous_week, ($end_date_of_previous_week . ' 23:59:59')])->count();


            $data["this_month_data_count"] = clone $data_query;
            $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('created_at', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


            $data["previous_month_data_count"] = clone $data_query;
            $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween('created_at', [$start_date_of_previous_month, ($end_date_of_previous_month . ' 23:59:59')])->count();


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


            $data["leaves"] = $this->leaves(
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



            $data["holidays"] = $this->leaves(
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





            $data["widgets"]["employee_on_holiday"] = $this->employee_on_holiday(
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



            $data["widgets"]["employee_on_holiday"]["id"] = 2;


            $data["widgets"]["employee_on_holiday"]["widget_name"] = "employee_on_holiday";
            $data["widgets"]["employee_on_holiday"]["widget_type"] = "default";
            $data["widgets"]["employee_on_holiday"]["route"] =  '/employee/all-employees?is_on_holiday=1&';






            $data["widgets"]["upcoming_passport_expiries"] = $this->upcoming_passport_expiries(
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










            $data["widgets"]["upcoming_passport_expiries"]["widget_name"] = "upcoming_passport_expiries";
            $data["widgets"]["upcoming_passport_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_passport_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=passport&";







            $data["widgets"]["upcoming_visa_expiries"] = $this->upcoming_visa_expiries(
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







            $data["widgets"]["upcoming_visa_expiries"]["widget_name"] = "upcoming_visa_expiries";
            $data["widgets"]["upcoming_visa_expiries"]["widget_type"] = "multiple_upcoming_days";


            $data["widgets"]["upcoming_visa_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=visa&";





            $data["widgets"]["upcoming_right_to_work_expiries"] = $this->upcoming_right_to_work_expiries(
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









            $data["widgets"]["upcoming_right_to_work_expiries"]["widget_name"] = "upcoming_right_to_work_expiries";
            $data["widgets"]["upcoming_right_to_work_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_right_to_work_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=right_to_work&";





            $data["widgets"]["upcoming_sponsorship_expiries"] = $this->upcoming_sponsorship_expiries(
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









            $data["widgets"]["upcoming_sponsorship_expiries"]["widget_name"] = "upcoming_sponsorship_expiries";
            $data["widgets"]["upcoming_sponsorship_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_sponsorship_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=sponsorship&";




            $sponsorship_statuses = ['unassigned', 'assigned', 'visa_applied','visa_rejected','visa_grantes','withdrawal'];
            foreach ($sponsorship_statuses as $sponsorship_status) {
                $data["widgets"][($sponsorship_status . "_sponsorships")] = $this->sponsorships(
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








                $data["widgets"][($sponsorship_status . "_sponsorships")]["widget_name"] = ($sponsorship_status . "_sponsorships");

                $data["widgets"][($sponsorship_status . "_sponsorships")]["widget_type"] = "default";
                $data["widgets"][($sponsorship_status . "_sponsorships")]["route"] = '/employee/all-employees?sponsorship_status=' . $sponsorship_status . "&";

            }




            $data["widgets"]["upcoming_pension_expiries"] = $this->upcoming_pension_expiries(
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











            $data["widgets"]["upcoming_pension_expiries"]["widget_name"] = "upcoming_pension_expiries";

            $data["widgets"]["upcoming_pension_expiries"]["widget_type"] = "multiple_upcoming_days";

            $data["widgets"]["upcoming_pension_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=pension&";





            $pension_statuses = ["opt_in", "opt_out"];
            foreach ($pension_statuses as $pension_status) {
                $data["widgets"][($pension_status . "_pensions")] = $this->pensions(
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



                $data["widgets"][($pension_status . "_pensions")]["widget_name"] = ($pension_status . "_pensions");
                $data["widgets"][($pension_status . "_pensions")]["widget_type"] = "default";
                $data["widgets"][($pension_status . "_pensions")]["route"] = '/employee/all-employees?pension_scheme_status=' . $pension_status . "&";
            }

            $employment_statuses = $this->getEmploymentStatuses();

            foreach ($employment_statuses as $employment_status) {
                $data["widgets"]["emplooyment_status_wise"]["data"][($employment_status->name . "_employees")] = $this->employees_by_employment_status(
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


                $data["widgets"]["emplooyment_status_wise"]["widget_name"] = "employment_status_wise_employee";
                $data["widgets"]["emplooyment_status_wise"]["widget_type"] = "graph";

                $data["widgets"]["emplooyment_status_wise"]["route"] = ('/employee/?status=' . $employment_status->name . "&");
            }


        return response()->json($data, 200);



















































             return response()->json($data, 200);
         } catch (Exception $e) {
             return $this->sendError($e, 500, $request);
         }
     }

































}
