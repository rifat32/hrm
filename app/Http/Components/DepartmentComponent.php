<?php
namespace App\Http\Components;

use App\Models\Department;

class DepartmentComponent {
    public function all_parent_departments_of_user($user_id) {
        $all_parent_department_ids = [];
        $assigned_departments = Department::whereHas("users", function ($query) use ($user_id) {
            $query->where("users.id", $user_id);
        })->get();


        foreach ($assigned_departments as $assigned_department) {
            array_push($all_parent_department_ids, $assigned_department->id);
            $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
        }

        return $all_parent_department_ids;
    }


}
