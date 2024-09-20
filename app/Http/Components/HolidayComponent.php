<?php
namespace App\Http\Components;

use App\Models\Holiday;
use Carbon\Carbon;

class HolidayComponent {
    public function get_holiday_details($in_date,$user_id, $all_parent_department_ids)
    {

        $holiday =   Holiday::where([
            "business_id" => auth()->user()->business_id
        ])
        ->where('status','approved')
            ->whereDate('holidays.start_date', "<=", $in_date)
            ->whereDate('holidays.end_date', ">=", $in_date)
            ->where(function ($query) use ($user_id, $all_parent_department_ids) {
                $query->whereHas("users", function ($query) use ($user_id) {
                    $query->where([
                        "users.id" => $user_id
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

        // if ($holiday && $holiday->is_active && !auth()->user()->hasRole("business_owner")) {
        //         throw new Exception(("there is a holiday on date" . $in_date), 400);
        // }

        return $holiday;
    }
    public function get_holiday_dates($start_date, $end_date, $user_id, $all_parent_department_ids)
    {
        // Convert start and end dates to Carbon instances
        $range_start = Carbon::parse($start_date)->startOfDay(); // Start of the day for range start
        $range_end = Carbon::parse($end_date)->endOfDay(); // End of the day for range end

        // Fetch holidays
        $holidays = Holiday::where('business_id', auth()->user()->business_id)
            ->whereDate('holidays.start_date', '<=', $range_end)  // Holidays can start before or on the end date
            ->whereDate('holidays.end_date', '>=', $range_start)  // Holidays can end after or on the start date
            ->where('is_active', 1)
            ->where('status','approved')
            ->where(function ($query) use ($user_id, $all_parent_department_ids) {
                $query->whereHas('users', function ($query) use ($user_id) {
                    $query->where('users.id', $user_id);
                })
                ->orWhereHas('departments', function ($query) use ($all_parent_department_ids) {
                    $query->whereIn('departments.id', $all_parent_department_ids);
                })
                ->orWhere(function ($query) {
                    $query->whereDoesntHave('users')
                        ->whereDoesntHave('departments');
                });
            })
            ->select('id', 'start_date', 'end_date')
            ->get();

        // Collect holiday dates within the range
        $holiday_dates = [];

        foreach ($holidays as $holiday) {
            // Parse start and end dates
            $start_holiday_date = Carbon::parse($holiday->start_date)->startOfDay();
            $end_holiday_date = Carbon::parse($holiday->end_date)->endOfDay();

            // Adjust the holiday start and end dates based on the given range
            if ($start_holiday_date->lt($range_start)) {
                $start_holiday_date = $range_start;
            }
            if ($end_holiday_date->gt($range_end)) {
                $end_holiday_date = $range_end;
            }

            // Collect the dates within the adjusted range
            for ($date = $start_holiday_date->copy(); $date->lte($end_holiday_date); $date->addDay()) {
                $holiday_dates[] = $date->toDateString(); // Collect as date strings to avoid time part
            }
        }

        return collect($holiday_dates)->unique()->values();
    }




    public function get_weekend_dates($start_date,$end_date,$user_id, $work_shift_histories)
    {

        $weekend_dates =   collect(); // Initialize an empty collection to store weekend dates


        $work_shift_histories->each(function ($work_shift) use ($start_date, $end_date, &$weekend_dates, $user_id) {
            $weekends = $work_shift->details()->where("is_weekend", 1)->get();

            $weekends->each(function ($weekend) use ($start_date, $end_date, &$weekend_dates, $work_shift, $user_id) {
                $day_of_week = $weekend->day;

                // Determine the end date for the loop


                $userShift = $work_shift->users->first(function ($user) use ($user_id) {
                    return $user->id == $user_id;
                });
                $user_to_date = $userShift->pivot->to_date ?? null;

                if ($user_to_date) {
                    $end_date_loop = $user_to_date;
                } elseif ($work_shift->to_date) {
                    $end_date_loop = $work_shift->to_date;
                } else {
                    $end_date_loop = $end_date;
                }

                $user_from_date = $userShift->pivot->from_date;
                $start_date_loop = Carbon::parse($user_from_date)->gt($start_date) ? $user_from_date : $start_date;


                // Find the next occurrence of the specified day of the week
                $next_day = Carbon::parse($start_date_loop)->copy()->next($day_of_week);


                // Loop through the days until either the to_date or the end of the year
                while ($next_day <= $end_date_loop) {
                    $weekend_dates->push($next_day->format('d-m-Y'));
                    $next_day->addWeek(); // Move to the next week
                }
            });
        });

        return $weekend_dates;

    }






}
