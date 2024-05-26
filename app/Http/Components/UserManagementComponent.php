<?php

namespace App\Http\Components;

use App\Http\Utils\AttendanceUtil;
use App\Http\Utils\BasicUtil;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\LeaveRecord;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\User;
use App\Models\UserRecruitmentProcess;
use Carbon\Carbon;
use Exception;

class UserManagementComponent
{

use BasicUtil, AttendanceUtil;

protected $holidayComponent;
protected $workShiftHistoryComponent;
protected $leaveComponent;

public function __construct(WorkShiftHistoryComponent $workShiftHistoryComponent, HolidayComponent $holidayComponent, LeaveComponent $leaveComponent)
{

    $this->workShiftHistoryComponent = $workShiftHistoryComponent;
    $this->holidayComponent = $holidayComponent;
    $this->leaveComponent = $leaveComponent;


}

    public function updateUsersQuery($all_manager_department_ids,$usersQuery)
    {

        $total_departments = Department::where([
            "business_id" => auth()->user()->business_id,
            "is_active" => 1
        ])->count();

        $today = today();
        $usersQuery = $usersQuery->whereNotIn('id', [auth()->user()->id])
            ->when(empty(auth()->user()->business_id), function ($query)  {
                if (auth()->user()->hasRole("superadmin")) {
                    return  $query->where(function ($query) {
                        return   $query->where('business_id', NULL)
                            ->orWhere(function ($query) {
                                return $query
                                    ->whereNotNull("business_id")
                                    ->whereHas("roles", function ($query) {
                                        return $query->where("roles.name", "business_owner");
                                    });
                            });
                    });
                } else {
                    return  $query->where(function ($query) {
                        return   $query->where('created_by', auth()->user()->id);
                    });
                }
            })

            ->when(!empty(auth()->user()->business_id), function ($query) use ( $all_manager_department_ids) {
                return $query->where(function ($query) use ($all_manager_department_ids) {
                    return  $query->where('business_id', auth()->user()->business_id)
                        ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids);
                        });
                });
            })
            ->when(!empty(request()->role), function ($query)  {
                $rolesArray = explode(',', request()->role);
                return   $query->whereHas("roles", function ($q) use ($rolesArray) {
                    return $q->whereIn("name", $rolesArray);
                });
            })

            ->when(!empty(request()->not_in_rota), function ($query) {
                $query->whereDoesntHave("employee_rotas");
            })


            ->when(!empty(request()->full_name), function ($query)  {
                // Replace spaces with commas and create an array
                $searchTerms = explode(',', str_replace(' ', ',', request()->full_name));

                $query->where(function ($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query->orWhere(function ($subquery) use ($term) {
                            $subquery->where("first_Name", "like", "%" . $term . "%")
                                ->orWhere("last_Name", "like", "%" . $term . "%")
                                ->orWhere("middle_Name", "like", "%" . $term . "%");
                        });
                    }
                });
            })



            ->when(!empty(request()->user_id), function ($query)  {
                return   $query->where([
                    "user_id" => request()->user_id
                ]);
            })

            ->when(!empty(request()->email), function ($query)  {
                return   $query->where([
                    "email" => request()->email
                ]);
            })


            ->when(!empty(request()->designation_id), function ($query)  {
                $idsArray = explode(',', request()->designation_id);
                return $query->whereIn('designation_id', $idsArray);
            })

            ->when(!empty(request()->employment_status_id), function ($query)  {
                $idsArray = explode(',', request()->employment_status_id);
                return $query->whereIn('employment_status_id', ($idsArray));
            })

            ->when(!empty(request()->search_key), function ($query)  {
                $term = request()->search_key;
                return $query->where(function ($subquery) use ($term) {
                    $subquery->where("first_Name", "like", "%" . $term . "%")
                        ->orWhere("last_Name", "like", "%" . $term . "%")
                        ->orWhere("email", "like", "%" . $term . "%")
                        ->orWhere("phone", "like", "%" . $term . "%");
                });
            })


            // ->when(isset(request()->is_in_employee), function ($query)  {
            //     return $query->where('is_in_employee', intval(request()->is_in_employee));
            // })

            ->when(isset(request()->is_on_holiday), function ($query) use ($today, $total_departments) {
                if (intval(request()->is_on_holiday) == 1) {
                    $query
                        ->where("business_id", auth()->user()->business_id)

                        ->where(function ($query) use ($today, $total_departments) {
                            $query->where(function ($query) use ($today, $total_departments) {
                                $query->where(function ($query) use ($today, $total_departments) {
                                    $query->whereHas('holidays', function ($query) use ($today) {
                                        $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay())
                                            ->where('holidays.end_date', ">=",  $today->copy()->endOfDay());
                                    })
                                        ->orWhere(function ($query) use ($today, $total_departments) {
                                            $query->whereHasRecursiveHolidays($today, $total_departments);
                                        });
                                })
                                    ->where(function ($query) use ($today) {
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
                                    function ($query) use ($today) {
                                        $query->orWhereDoesntHave('holidays', function ($query) use ($today) {
                                            $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay());
                                            $query->where('holidays.end_date', ">=",  $today->copy()->endOfDay());
                                            $query->doesntHave('users');
                                        });
                                    }
                                );
                        });
                } else {
                    // Inverted logic for when employees are not on holiday
                    $query->where(function ($query) use ($today, $total_departments) {
                        $query->whereDoesntHave('holidays')
                            ->orWhere(function ($query) use ($today, $total_departments) {
                                $query->whereDoesntHave('departments')
                                    ->orWhereHas('departments', function ($subQuery) use ($today, $total_departments) {
                                        $subQuery->whereDoesntHave('holidays');
                                    });
                            });
                    });
                }
            })


            ->when(!empty(request()->upcoming_expiries), function ($query)  {

                if (request()->upcoming_expiries == "passport") {
                    $query->whereHas("passport_detail", function ($query) {
                        $query->where("employee_passport_detail_histories.passport_expiry_date", ">=", today());
                    });
                } else if (request()->upcoming_expiries == "visa") {
                    $query->whereHas("visa_detail", function ($query) {
                        $query->where("employee_visa_detail_histories.visa_expiry_date", ">=", today());
                    });
                } else if (request()->upcoming_expiries == "right_to_work") {
                    $query->whereHas("right_to_work", function ($query) {
                        $query->where("employee_right_to_work_histories.right_to_work_expiry_date", ">=", today());
                    });
                } else if (request()->upcoming_expiries == "sponsorship") {
                    $query->whereHas("sponsorship_details", function ($query) {
                        $query->where("employee_sponsorship_histories.expiry_date", ">=", today());
                    });
                } else if (request()->upcoming_expiries == "pension") {
                    $query->whereHas("pension_details", function ($query) {
                        $query->where("employee_pensions.pension_re_enrollment_due_date", ">=", today());
                    });
                }
            })


            ->when(!empty(request()->immigration_status), function ($query)  {
                return $query->where('immigration_status', (request()->immigration_status));
            })
            ->when(!empty(request()->sponsorship_status), function ($query)  {
                return $query->whereHas("sponsorship_details", function ($query)  {
                    $query->where("employee_sponsorship_histories.status", request()->sponsorship_status);
                });
            })


            ->when(!empty(request()->sponsorship_note), function ($query)  {
                return $query->whereHas("sponsorship_details", function ($query)  {
                    $query->where("employee_sponsorship_histories.note", request()->sponsorship_note);
                });
            })
            ->when(!empty(request()->sponsorship_certificate_number), function ($query)  {
                return $query->whereHas("sponsorship_details", function ($query)  {
                    $query->where("employee_sponsorship_histories.certificate_number", request()->sponsorship_certificate_number);
                });
            })
            ->when(!empty(request()->sponsorship_current_certificate_status), function ($query)  {
                return $query->whereHas("sponsorship_details", function ($query)  {
                    $query->where("employee_sponsorship_histories.current_certificate_status", request()->sponsorship_current_certificate_status);
                });
            })
            ->when(isset(request()->sponsorship_is_sponsorship_withdrawn), function ($query)  {
                return $query->whereHas("sponsorship_details", function ($query)  {
                    $query->where("employee_sponsorship_histories.is_sponsorship_withdrawn", intval(request()->sponsorship_is_sponsorship_withdrawn));
                });
            })

            ->when(!empty(request()->project_id), function ($query)  {
                return $query->whereHas("projects", function ($query)  {
                    $query->where("projects.id", request()->project_id);
                });
            })
            ->when(!empty(request()->department_id), function ($query)  {
                return $query->whereHas("departments", function ($query)  {
                    $query->where("departments.id", request()->department_id);
                });
            })


            ->when(!empty(request()->work_location_id), function ($query)  {
                return $query->where('work_location_id', (request()->work_location_id));
            })
            ->when(!empty(request()->holiday_id), function ($query)  {
                return $query->whereHas("holidays", function ($query)  {
                    $query->where("holidays.id", request()->holiday_id);
                });
            })
            ->when(isset(request()->is_active), function ($query)  {
                return $query->where('is_active', intval(request()->is_active));
            })

            ->when(!empty(request()->start_joining_date), function ($query)  {
                return $query->where('joining_date', ">=", request()->start_joining_date);
            })
            ->when(!empty(request()->end_joining_date), function ($query)  {
                return $query->where('joining_date', "<=", (request()->end_joining_date .  ' 23:59:59'));
            })
            ->when(!empty(request()->start_sponsorship_date_assigned), function ($query)  {
                return $query->whereHas("sponsorship_details", function ($query)  {
                    $query->where("employee_sponsorship_histories.date_assigned", ">=", (request()->start_sponsorship_date_assigned));
                });
            })
            ->when(!empty(request()->end_sponsorship_date_assigned), function ($query)  {
                return $query->whereHas("sponsorship_details", function ($query)  {
                    $query->where("employee_sponsorship_histories.date_assigned", "<=", (request()->end_sponsorship_date_assigned . ' 23:59:59'));
                });
            })


            ->when(!empty(request()->start_sponsorship_expiry_date), function ($query)  {
                return $query->whereHas("sponsorship_details", function ($query)  {
                    $query->where("employee_sponsorship_histories.expiry_date", ">=", request()->start_sponsorship_expiry_date);
                });
            })
            ->when(!empty(request()->end_sponsorship_expiry_date), function ($query)  {
                return $query->whereHas("sponsorship_details", function ($query)  {
                    $query->where("employee_sponsorship_histories.expiry_date", "<=", request()->end_sponsorship_expiry_date . ' 23:59:59');
                });
            })
            ->when(!empty(request()->sponsorship_expires_in_day), function ($query) use ( $today) {
                return $query->whereHas("sponsorship_details", function ($query) use ( $today) {
                    $query_day = Carbon::now()->addDays(request()->sponsorship_expires_in_day);
                    $query->whereBetween("employee_sponsorship_histories.expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                });
            })



            ->when(!empty(request()->start_pension_pension_enrollment_issue_date), function ($query)  {
                return $query->whereHas("pension_details", function ($query)  {
                    $query->where("employee_pension_histories.pension_enrollment_issue_date", ">=", (request()->start_pension_pension_enrollment_issue_date));
                });
            })
            ->when(!empty(request()->end_pension_pension_enrollment_issue_date), function ($query)  {
                return $query->whereHas("pension_details", function ($query)  {
                    $query->where("employee_pension_histories.pension_enrollment_issue_date", "<=", (request()->end_pension_pension_enrollment_issue_date . ' 23:59:59'));
                });
            })


            ->when(!empty(request()->start_pension_pension_re_enrollment_due_date), function ($query)  {
                return $query->whereHas("pension_details", function ($query)  {
                    $query->where("employee_pension_histories.pension_re_enrollment_due_date", ">=", request()->start_pension_pension_re_enrollment_due_date);
                });
            })
            ->when(!empty(request()->end_pension_pension_re_enrollment_due_date), function ($query)  {
                return $query->whereHas("pension_details", function ($query)  {
                    $query->where("employee_pension_histories.pension_re_enrollment_due_date", "<=", request()->end_pension_pension_re_enrollment_due_date . ' 23:59:59');
                });
            })
            ->when(!empty(request()->pension_pension_re_enrollment_due_date_in_day), function ($query) use ( $today) {
                return $query->whereHas("pension_details", function ($query) use ( $today) {
                    $query_day = Carbon::now()->addDays(request()->pension_pension_re_enrollment_due_date_in_day);
                    $query->whereBetween("employee_pension_histories.pension_re_enrollment_due_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                });
            })

            ->when(!empty(request()->pension_scheme_status), function ($query)  {
                return $query->whereHas("pension_details", function ($query)  {
                    $query->where("employee_pension_histories.pension_scheme_status", request()->pension_scheme_status);
                });
            })


            ->when(!empty(request()->start_passport_issue_date), function ($query)  {
                return $query->whereHas("passport_details", function ($query)  {
                    $query->where("employee_passport_detail_histories.passport_issue_date", ">=", request()->start_passport_issue_date);
                });
            })
            ->when(!empty(request()->end_passport_issue_date), function ($query)  {
                return $query->whereHas("passport_details", function ($query)  {
                    $query->where("employee_passport_detail_histories.passport_issue_date", "<=", request()->end_passport_issue_date . ' 23:59:59');
                });
            })


            ->when(!empty(request()->start_passport_expiry_date), function ($query)  {
                return $query->whereHas("passport_details", function ($query)  {
                    $query->where("employee_passport_detail_histories.passport_expiry_date", ">=", request()->start_passport_expiry_date);
                });
            })
            ->when(!empty(request()->end_passport_expiry_date), function ($query)  {
                return $query->whereHas("passport_details", function ($query)  {
                    $query->where("employee_passport_detail_histories.passport_expiry_date", "<=", request()->end_passport_expiry_date . ' 23:59:59');
                });
            })
            ->when(!empty(request()->passport_expires_in_day), function ($query) use ( $today) {
                return $query->whereHas("passport_details", function ($query) use ( $today) {
                    $query_day = Carbon::now()->addDays(request()->passport_expires_in_day);
                    $query->whereBetween("employee_passport_detail_histories.passport_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                });
            })
            ->when(!empty(request()->start_visa_issue_date), function ($query)  {
                return $query->whereHas("visa_details", function ($query)  {
                    $query->where("employee_visa_detail_histories.visa_issue_date", ">=", request()->start_visa_issue_date);
                });
            })
            ->when(!empty(request()->end_visa_issue_date), function ($query)  {
                return $query->whereHas("visa_details", function ($query)  {
                    $query->where("employee_visa_detail_histories.visa_issue_date", "<=", request()->end_visa_issue_date . ' 23:59:59');
                });
            })
            ->when(!empty(request()->start_visa_expiry_date), function ($query)  {
                return $query->whereHas("visa_details", function ($query)  {
                    $query->where("employee_visa_detail_histories.visa_expiry_date", ">=", request()->start_visa_expiry_date);
                });
            })
            ->when(!empty(request()->end_visa_expiry_date), function ($query)  {
                return $query->whereHas("visa_details", function ($query)  {
                    $query->where("employee_visa_detail_histories.visa_expiry_date", "<=", request()->end_visa_expiry_date . ' 23:59:59');
                });
            })
            ->when(!empty(request()->visa_expires_in_day), function ($query) use ( $today) {
                return $query->whereHas("visa_details", function ($query) use ( $today) {
                    $query_day = Carbon::now()->addDays(request()->visa_expires_in_day);
                    $query->whereBetween("employee_visa_detail_histories.visa_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                });
            })

            ->when(!empty(request()->start_right_to_work_check_date), function ($query)  {
                return $query->whereHas("right_to_works", function ($query)  {
                    $query->where("employee_right_to_work_histories.right_to_work_check_date", ">=", request()->start_right_to_work_check_date);
                });
            })
            ->when(!empty(request()->end_right_to_work_check_date), function ($query)  {
                return $query->whereHas("right_to_works", function ($query)  {
                    $query->where("employee_right_to_work_histories.right_to_work_check_date", "<=", request()->end_right_to_work_check_date . ' 23:59:59');
                });
            })
            ->when(!empty(request()->start_right_to_work_expiry_date), function ($query)  {
                return $query->whereHas("right_to_works", function ($query)  {
                    $query->where("employee_right_to_work_histories.right_to_work_expiry_date", ">=", request()->start_right_to_work_expiry_date);
                });
            })
            ->when(!empty(request()->end_right_to_work_expiry_date), function ($query)  {
                return $query->whereHas("right_to_works", function ($query)  {
                    $query->where("employee_right_to_work_histories.right_to_work_expiry_date", "<=", request()->end_right_to_work_expiry_date . ' 23:59:59');
                });
            })
            ->when(!empty(request()->right_to_work_expires_in_day), function ($query) use ( $today) {
                return $query->whereHas("right_to_works", function ($query) use ( $today) {
                    $query_day = Carbon::now()->addDays(request()->right_to_work_expires_in_day);
                    $query->whereBetween("employee_right_to_work_histories.right_to_work_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                });
            })
            ->when(isset(request()->doesnt_have_payrun), function ($query)  {
                if (intval(request()->doesnt_have_payrun)) {
                    return $query->whereDoesntHave("payrun_users");
                } else {
                    return $query;
                }
            })

            ->when(!empty(request()->start_date), function ($query)  {
                return $query->where('created_at', ">=", request()->start_date);
            })
            ->when(!empty(request()->end_date), function ($query)  {
                return $query->where('created_at', "<=", (request()->end_date . ' 23:59:59'));
            });

        return $usersQuery;
    }

    public function getLeaveDetailsByUserIdfunc($id,$all_manager_department_ids) {
         // get appropriate use if auth user have access
         $user = $this->getUserByIdUtil($id, $all_manager_department_ids);



         $created_by  = NULL;
         if (auth()->user()->business) {
             $created_by = auth()->user()->business->created_by;
         }

         $setting_leave = SettingLeave::where('setting_leaves.business_id', auth()->user()->business_id)
             ->where('setting_leaves.is_default', 0)
             ->first();
         if (!$setting_leave) {
            throw new Exception("No leave setting found.",409);
         }

         if (!$setting_leave->start_month) {
             $setting_leave->start_month = 1;
         }

         // $paid_leave_available = in_array($user->employment_status_id, $setting_leave->paid_leave_employment_statuses()->pluck("employment_statuses.id")->toArray());



         $leave_types =   SettingLeaveType::where(function ($query) use ( $user,$created_by) {
             $query->where('setting_leave_types.business_id', auth()->user()->business_id)
                 ->where('setting_leave_types.is_default', 0)
                 ->where('setting_leave_types.is_active', 1)
                 // ->when($paid_leave_available == 0, function ($query) {
                 //     $query->where('setting_leave_types.type', "unpaid");
                 // })
                 ->where(function($query) use($user){
                    $query->whereHas("employment_statuses", function($query) use($user){
                        if ($user->employment_status && $user->employment_status->id) {
                            $query->whereIn("employment_statuses.id", [$user->employment_status->id]);
                        }

                    })
                    ->orWhereDoesntHave("employment_statuses");
                 })
                 ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                     $q->whereIn("disabled_setting_leave_types.created_by", [$created_by]);
                 })
                 ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                     $q->whereIn("disabled_setting_leave_types.business_id", [auth()->user()->business_id]);
                 });
         })
             ->get();

             $startOfMonth = Carbon::create(null, $setting_leave->start_month, 1, 0, 0, 0)->subYear();
         foreach ($leave_types as $key => $leave_type) {
             $total_recorded_hours = LeaveRecord::whereHas('leave', function ($query) use ($user, $leave_type) {
                 $query->where([
                     "user_id" => $user->id,
                     "leave_type_id" => $leave_type->id

                 ]);
             })
                 ->where("leave_records.date", ">=", $startOfMonth)
                 ->get()
                 ->sum(function ($record) {
                     return Carbon::parse($record->end_time)->diffInHours(Carbon::parse($record->start_time));
                 });
             $leave_types[$key]->already_taken_hours = $total_recorded_hours;
         }
         return $leave_types;
    }

    public function getRecruitmentProcessesByUserIdFunc($id,$all_manager_department_ids) {
        $user = $this->getUserByIdUtil($id,$all_manager_department_ids);

        $user_recruitment_processes = UserRecruitmentProcess::with("recruitment_process")
            ->where([
                "user_id" => $user->id
            ])
            ->whereNotNull("description")
            ->get();

            return $user_recruitment_processes;
    }


public function getScheduleInformationData ($user_id,$start_date,$end_date){


    $all_parent_department_ids = $this->all_parent_departments_of_user($user_id);

    // Process holiday dates
    $holiday_dates =  $this->holidayComponent->get_holiday_dates($start_date, $end_date, $user_id, $all_parent_department_ids);

    $work_shift_histories = $this->workShiftHistoryComponent->get_work_shift_histories($start_date, $end_date, $user_id,false);

    if(empty($work_shift_histories)) {
        return [
            "schedule_data" => [],
            "total_capacity_hours" => 0
        ];
    }



    $weekend_dates = $this->holidayComponent->get_weekend_dates($start_date, $end_date, $user_id, $work_shift_histories);

    // Process already taken leave hourly dates
    $already_taken_leave_dates = $this->leaveComponent->get_already_taken_leave_dates($start_date, $end_date, $user_id, false);

    // Merge the collections and remove duplicates
    $all_leaves_collection = collect($holiday_dates)->merge($weekend_dates)->merge($already_taken_leave_dates)->unique();


    // $result_collection now contains all unique dates from holidays and weekends
    $all_leaves_array = $all_leaves_collection->values()->all();



    $start_date = Carbon::parse($start_date)->toDateString();
$end_date = Carbon::parse($end_date)->toDateString();

    $all_dates = collect(range(strtotime($start_date), strtotime($end_date), 86400)) // 86400 seconds in a day
        ->map(function ($timestamp) {
            return date('Y-m-d', $timestamp);
        });



    $all_scheduled_dates = $all_dates->reject(fn ($date) => in_array($date, $all_leaves_array));



    $schedule_data = [];
    $total_capacity_hours = 0;

    $all_scheduled_dates->each(function ($date) use (&$schedule_data, &$total_capacity_hours, $user_id) {

        $work_shift_history =  $this->workShiftHistoryComponent->get_work_shift_history($date, $user_id);
        $work_shift_details =  $this->workShiftHistoryComponent->get_work_shift_details($work_shift_history, $date);

        if ($work_shift_details) {
            if (!$work_shift_details->start_at || !$work_shift_details->end_at) {
                return false;
            }
            $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
            $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
            $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);



            $schedule_data[] = [
                "date" => $date,
                "capacity_hours" => $capacity_hours,
                "break_type" => $work_shift_history->break_type,
                "break_hours" => $work_shift_history->break_hours,
                "start_at" => $work_shift_details->start_at,
                'end_at' => $work_shift_details->end_at,
                'is_weekend' => $work_shift_details->is_weekend,
            ];
            $total_capacity_hours += $capacity_hours;
        }
    });
    return [
        "schedule_data" => $schedule_data,
        "total_capacity_hours" => $total_capacity_hours
    ];
}


public function getTotalPresentHours($user_id,$start_date,$end_date) {


    $attendances = Attendance::where([
        "user_id" => $user_id
    ])
    ->where('in_date', '>=', $start_date . ' 00:00:00')
    ->where('in_date', '<=', ($end_date . ' 23:59:59'))
    ->get();


    $total_regular_hours = 0;
    $total_overtime_hours = 0;

    foreach($attendances as $attendance){
        $present_hours = $this->calculate_total_present_hours($attendance->attendance_records);
       $overtime_hours = $this->calculateOvertime($attendance);
       $regular_hours = $present_hours - $overtime_hours;

       $total_regular_hours += $regular_hours;
       $total_overtime_hours += $overtime_hours;

    }

    return [
        "total_regular_hours" => $total_regular_hours,
        "total_overtime_hours" => $total_overtime_hours
    ];


}







public function getRotaData($user_id,$joining_date) {


    $joiningDate = Carbon::parse($joining_date);

    // Helper function to adjust start dates
    $adjustDate = function($date) use ($joiningDate) {
        return $date->lessThan($joiningDate) ? $joiningDate : $date;
    };




    $startOfToday = $adjustDate(Carbon::today());
    $endOfToday = $adjustDate(Carbon::today()->endOfDay());

    $startOfWeek = $adjustDate(Carbon::now()->startOfWeek());
    $endOfWeek = $adjustDate(Carbon::now()->endOfWeek());

    $startOfMonth = $adjustDate(Carbon::now()->startOfMonth());
    $endOfMonth = $adjustDate(Carbon::now()->endOfMonth());





$data["today"]["total_capacity_hours"] = $this->getScheduleInformationData($user_id,$startOfToday,$endOfToday)["total_capacity_hours"];
$data["today"]["total_present_hours"] = $this->getTotalPresentHours($user_id,$startOfToday,$endOfToday);




$data["this_week"]["total_capacity_hours"] = $this->getScheduleInformationData($user_id,$startOfWeek,$endOfWeek)["total_capacity_hours"];
$data["this_week"]["total_present_hours"] = $this->getTotalPresentHours($user_id,$startOfWeek,$endOfWeek);




$data["this_month"]["total_capacity_hours"] = $this->getScheduleInformationData($user_id,$startOfMonth,$endOfMonth)["total_capacity_hours"];
$data["this_month"]["total_present_hours"] = $this->getTotalPresentHours($user_id,$startOfMonth,$endOfMonth);



return $data;




















}







}
