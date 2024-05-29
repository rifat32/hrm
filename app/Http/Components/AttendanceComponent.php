<?php
namespace App\Http\Components;

use App\Http\Utils\BasicUtil;
use App\Models\Attendance;
use App\Models\SettingAttendance;
use Carbon\Carbon;
use Exception;

class AttendanceComponent {
use BasicUtil;

    public function checkAttendanceExists($id="",$user_id,$date) {
        $exists = Attendance::when(!empty($id), function($query) use($id) {
            $query->whereNotIn('id', [$id]);
        })
            ->where('attendances.user_id', $user_id)
            ->where('attendances.in_date', $date)
            ->where('attendances.business_id', auth()->user()->business_id)
            ->exists(); ;
        return $exists;
    }



    public function get_already_taken_attendance_dates($user_id,$start_date,$end_date) {
        $already_taken_attendances =  Attendance::where([
            "user_id" => $user_id
        ])
            ->where('attendances.in_date', '>=', $start_date)
            ->where('attendances.in_date', '<=', $end_date . ' 23:59:59')
            ->get();



        $already_taken_attendance_dates = $already_taken_attendances->map(function ($attendance) {
            return Carbon::parse($attendance->in_date)->format('d-m-Y');
        });

        return $already_taken_attendance_dates;
    }


    public function updateAttendanceQuery( $all_manager_department_ids,$attendancesQuery)
    {

        $attendancesQuery = $attendancesQuery
        ->where(
            [
                "attendances.business_id" => auth()->user()->business_id
            ]
        )
        ->when(!empty(request()->search_key), function ($query)  {
            return $query->where(function ($query)  {
                $term = request()->search_key;
                // $query->where("attendances.name", "like", "%" . $term . "%")
                //     ->orWhere("attendances.description", "like", "%" . $term . "%");
            });
        })
        ->when(!empty(request()->user_id), function ($query) {
            $idsArray = explode(',', request()->user_id);
            return $query->whereIn('attendances.user_id', $idsArray);
        })




        ->when(!empty(request()->overtime), function ($query) {
            $number_query = explode(',', str_replace(' ', ',', request()->overtime));
            return $query->where('attendances.overtime_hours', $number_query);
        })


        ->when(!empty(request()->schedule_hour), function ($query) {
            $number_query = explode(',', str_replace(' ', ',', request()->schedule_hour));
            return $query->where('attendances.capacity_hours', $number_query);
        })

        ->when(!empty(request()->break_hour), function ($query) {
            $number_query = explode(',', str_replace(' ', ',', request()->break_hour));
            return $query->where('attendances.break_hours', $number_query);
        })

        ->when(!empty(request()->worked_hour), function ($query) {
            $number_query = explode(',', str_replace(' ', ',', request()->worked_hour));
            return $query->where('attendances.total_paid_hours', $number_query[0], $number_query[1]);
        })

        ->when(!empty(request()->work_location_id), function ($query) {
            return $query->where('attendances.user_id', request()->work_location_id);
        })

        ->when(!empty(request()->project_id), function ($query) {
            return $query->where('attendances.project_id', request()->project_id);
        })
        ->when(!empty(request()->work_location_id), function ($query) {
            return $query->where('attendances.work_location_id', request()->work_location_id);
        })

        ->when(!empty(request()->status), function ($query)  {
            return $query->where('attendances.status', request()->status);
        })
        ->when(!empty(request()->department_id), function ($query) {
            return $query->whereHas("employee.departments", function ($query) {
                $query->where("departments.id", request()->department_id);
            });
        })





        ->when(
            (request()->has('show_my_data') && intval(request()->show_my_data) == 1),
            function ($query)  {
                $query->where('attendances.user_id', auth()->user()->id);
            },
            function ($query) use ($all_manager_department_ids,) {

                $query->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);

                });

            }
        )





        ->when(!empty(request()->start_date), function ($query) {
            return $query->where('attendances.in_date', ">=", request()->start_date);
        })
        ->when(!empty(request()->end_date), function ($query) {
            return $query->where('attendances.in_date', "<=", (request()->end_date . ' 23:59:59'));
        });

        return $attendancesQuery;
    }

    public function get_attendance_setting()
    {
        $setting_attendance = SettingAttendance::where([
            "business_id" => auth()->user()->business_id
        ])
            ->first();
        if (!$setting_attendance) {
            throw new Exception("Please define attendance setting first", 400);
        }
        return $setting_attendance;
    }


    public function getAttendanceV2Data(){

  // Retrieve all department IDs managed by the current user.
        $all_manager_department_ids = $this->get_all_departments_of_manager();

   // Retrieve attendance settings.
        $setting_attendance = $this->get_attendance_setting();

         // Query for attendance records with related data.
        $attendancesQuery = Attendance::with([
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
        ]);


 // Update attendance query based on manager's departmental access.

        $attendancesQuery = $this->updateAttendanceQuery($all_manager_department_ids, $attendancesQuery);

            // Retrieve attendance data.
            $attendances = $this->retrieveData($attendancesQuery, "attendances.id");

            $data['data'] = $attendances;

            // Calculate total active hours.
            $data['data_highlights']['total_active_hours'] = $attendances->sum('total_paid_hours');

                // Calculate total extra hours.
            $data['data_highlights']['total_extra_hours'] = $attendances->sum('overtime_hours');

    // Calculate behavior counts.
            $behavior_counts = [
                'absent' => $attendances->filter(fn ($attendance) => ($attendance->behavior === 'absent'|| $attendance->is_present === 0))->count(),
                'regular' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'regular')->count(),
                'early' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'early')->count(),
                'late' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'late')->count(),
            ];

               // Determine the most frequent behavior.
            $max_behavior = max($behavior_counts);
            if ($attendances->isEmpty()) {
                $data['data_highlights']['behavior'] = $behavior_counts;
                $data['data_highlights']['average_behavior'] = "no data";
                $data['data_highlights']['total_schedule_hours'] = 0;
            } else {
                $data['data_highlights']['behavior'] = $behavior_counts;
                $data['data_highlights']['average_behavior'] = array_search($max_behavior, $behavior_counts);
                $data['data_highlights']['total_schedule_hours'] = $attendances->sum('capacity_hours');
            }


             // Calculate total leave hours.
            // $data['data_highlights']['total_leave_hours'] =  $attendances->sum('leave_hours');
            // $data['data_highlights']['total_leave_hours'] =  0;

             // Calculate total available hours.
            $total_available_hours = $data['data_highlights']['total_active_hours'] - $data['data_highlights']['total_extra_hours'];


                // Calculate work availability percentage.
            if ($total_available_hours == 0 || $data['data_highlights']['total_schedule_hours'] == 0) {
                $data['data_highlights']['total_work_availability_per_centum'] = 0;
            } else {
                $data['data_highlights']['total_work_availability_per_centum'] = ($total_available_hours / $data['data_highlights']['total_schedule_hours']) * 100;
            }

  // Determine work availability status based on settings.
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




            return $data;


    }




}
