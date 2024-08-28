<?php

->when(
    request()->boolean('show_all_data'),
    function ($query) use ($all_manager_department_ids) {
        $query->where('leaves.user_id', auth()->user()->id)
            ->orWhere(function ($query) use ($all_manager_department_ids) {
                $query->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                    ->whereNotIn('leaves.user_id', [auth()->user()->id]);
            });
    },
    function ($query) use ($all_manager_department_ids) {
        $query->when(
            (request()->boolean('show_my_data')),
            function ($query) {
                $query->where('leaves.user_id', auth()->user()->id);
            },
            function ($query) use ($all_manager_department_ids,) {

                $query->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                    ->whereNotIn('leaves.user_id', [auth()->user()->id]);;
            }
        );
    }
)
