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
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

trait LeaveUtil
{
    use ErrorUtil;

    public function processLeaveApproval($leave,$is_approved=0) {
        $leave = Leave::where([
            "id" => $leave->id,
            "business_id" => auth()->user()->business_id
        ])
            ->first();


        if (!$leave->employee) {

            throw new Exception("No Employee for the leave found",400);

        }



        $leave->status = "in_progress";
        $setting_leave = SettingLeave::where([
            "business_id" => auth()->user()->business_id,
            "is_default" => 0
        ])->first();

       $special_user_ids = $setting_leave->special_users()->pluck("id");

       $special_role_ids =  $setting_leave->special_roles()->pluck("role_id");

        if ($setting_leave->approval_level == "single") {
            $leave_approval = LeaveApproval::where([
                "leave_id" => $leave->id,
            ])
            ->whereIn("created_by",$special_user_ids->toArray())
            ->orderBy("id","DESC")
            ->select(
                "leave_approvals.id",
                "leave_approvals.is_approved"
            )
            ->first();



                $user = auth()->user();




                $is_special_user =  $special_user_ids->contains($user->id);
                if ($is_special_user) {
                    if($leave_approval->is_approved) {
                        $leave->status = "approved";
                    }else {
                        $leave->status = "rejected";
                    }


                }
                else {

                    $role_names = $user->getRoleNames()->toArray();

                    $role_ids =  Role::whereIn("name", $role_names)->pluck("roles.id");



                    $special_role = $special_role_ids->contains(function ($value) use ($role_ids) {
                        return in_array($value, $role_ids);
                    });

                    if($special_role) {
                            if($leave_approval->is_approved) {
                                $leave->status = "approved";
                            }else {
                                $leave->status = "rejected";
                            }

                    }
                }



        }
        if ($setting_leave->approval_level == "multiple") {

            $not_approved_manager_found = false;

            $department = Department::whereHas('users', function ($query) use ($leave) {
                $query->where('departments.id', $leave->employee->departments[0]->id);
            })->first();



            if (!$department) {
                return [
                    "success" => false,
                    "message" => "Hey please specify department for the employee first!",
                    "status" => 400
                ];

            }


            $parentData = [$department];

            $parent = clone $department;

            while (!empty($parent->parent)) {

                $parentData[] = $parent->parent;
                $parent = clone $parent->parent;

            }

            $parentData = array_reverse($parentData);



            foreach($parentData as $single_department) {
                 $verify_leave_approval =   LeaveApproval::where([
                        'leave_id' => $leave->id,
                        'is_approved' => 1,
                        "created_by" => $single_department->manager_id
                    ])->latest()->first();
                if(!$verify_leave_approval) {
                    $not_approved_manager_found = true;
                    break;
                }
            }

            if(!$not_approved_manager_found || auth()->user()->hasRole("business_owner") ) {
                if($is_approved) {
                    $leave->status = "approved";
                }else {
                    $leave->status = "rejected";
                }

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
