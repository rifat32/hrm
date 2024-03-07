<?php
namespace App\Http\Components;

use App\Models\Holiday;

class HolidayComponent {
    public function get_holiday_details($in_date,$user_id, $all_parent_department_ids)
    {

        $holiday =   Holiday::where([
            "business_id" => auth()->user()->business_id
        ])
            ->where('holidays.start_date', "<=", $in_date)
            ->where('holidays.end_date', ">=", $in_date . ' 23:59:59')
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
}
