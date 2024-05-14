<?php
namespace App\Http\Components;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceComponent {


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


    public function updateAttendanceQuery($request, $all_manager_department_ids,$attendancesQuery)
    {


        $attendancesQuery = $attendancesQuery
        ->where(
            [
                "attendances.business_id" => auth()->user()->business_id
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
        });

        return $attendancesQuery;
    }






}
