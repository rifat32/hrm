<?php

namespace App\Http\Utils;

use App\Models\Department;
use App\Models\ErrorLog;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\Role;
use App\Models\SettingLeave;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

trait LeaveUtil
{

    public function processLeaveApproval($leave_id) {
        $leave = Leave::where([
            "id" => $leave_id,
            "business_id" => auth()->user()->business_id
        ])
            ->first();
        if (!$leave) {
            return [
                "success" => false,
                "message" => "No leave request found",
                "status" => 400
            ];
        }
        $leave->status = "progress";
        $setting_leave = SettingLeave::where([
            "business_id" => auth()->user()->business_id,
            "is_default" => 0
        ])->first();

        if ($setting_leave->approval_level == "single") {
            $leave_approvals = LeaveApproval::where([
                "leave_id" => $leave->id,
                "is_approved" => 1
            ])->get();
            foreach ($leave_approvals as $single_leave_approval) {
                $user = User::where([
                    "id" =>  $single_leave_approval->created_by
                ])
                    ->first();
                $role_names = $user->getRoleNames();

                $special_user = $setting_leave->special_users()->where(["user_id" => $user->id])->first();
                if ($special_user) {
                    $leave->status = "approved";
                    break;
                }

                $roles =  Role::whereIn("name", $role_names)->get();
                foreach ($roles as $role) {
                    $special_role = $setting_leave->special_users()->where(["user_id" => $role->id])->first();
                    if ($special_role) {
                        $leave->status = "approved";
                        break 2;
                    }
                }
            }
        }
        if ($setting_leave->approval_level == "multiple") {

            $not_approved_manager_found = false;

            $department = Department::whereHas('users', function ($query) use ($leave) {
                $query->where('id', $leave->employee_id);
            })->first();

            if (!$department) {
                return [
                    "success" => false,
                    "message" => "Hey please specify department for the employee first!",
                    "status" => 400
                ];

            }
            $all_departments = $department->all_parent_data;
            foreach($all_departments as $single_department) {
                 $verify_leave_approval =   LeaveApproval::where([
                        'leave_id' => $leave->id,
                        'is_approved' => 1,
                        "created_by" => $single_department->manager_id
                    ])->first();
                if(!$verify_leave_approval) {
                    $not_approved_manager_found = true;
                    break;
                }
            }

            if(!$not_approved_manager_found ) {
                $leave->status = "approved";
            }
        }

        $leave->save();
        return [
            "success" => true,
            "message" => "",
            "status" => ''
        ];

    }


}
