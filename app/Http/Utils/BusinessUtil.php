<?php

namespace App\Http\Utils;


use App\Models\Business;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\JobPlatform;
use App\Models\Role;
use App\Models\SettingAttendance;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Exception;

trait BusinessUtil
{
    // this function do all the task and returns transaction id or -1



    public function businessOwnerCheck($business_id)
    {

        $businessQuery  = Business::where(["id" => $business_id]);
        if (!auth()->user()->hasRole('superadmin')) {
            $businessQuery = $businessQuery->where(function ($query) {
                $query->where('created_by', auth()->user()->id)
                    ->orWhere('owner_id', auth()->user()->id);
            });
        }

        $business =  $businessQuery->first();
        if (!$business) {
            return false;
        }
        return $business;
    }
    public function checkLeaveType($id)
    {
        $setting_leave_type  = SettingLeaveType::where(["id" => $id])->first();
        if (!$setting_leave_type) {
            return [
                "ok" => false,
                "status" => 400,
                "message" => "Leave type does not exists."
            ];
        }

        if ($setting_leave_type->business_id != auth()->user()->business_id) {
            return [
                "ok" => false,
                "status" => 403,
                "message" => "Leave type belongs to another business."
            ];
        }

        return [
            "ok" => true,
        ];
    }

    public function checkUser($id)
    {
        $user  = User::where(["id" => $id])->first();
        if (!$user) {
            return [
                "ok" => false,
                "status" => 400,
                "message" => "User does not exists."
            ];
        }

        if ($user->business_id != auth()->user()->business_id) {
            return [
                "ok" => false,
                "status" => 403,
                "message" => "User belongs to another business."
            ];
        }

        return [
            "ok" => true,
        ];
    }
    public function checkRole($role)
    {

        // if(!empty(auth()->user()->business_id)) {
        //     $role = $role . "#" . auth()->user()->business_id;
        // }


        $role  = Role::where(["name" => $role])->first();


        if (!$role) {
            return [
                "ok" => false,
                "status" => 400,
                "message" => "Role does not exists."
            ];
        }

        if (!empty(auth()->user()->business_id)) {
            if ($role->business_id != auth()->user()->business_id) {
                return [
                    "ok" => false,
                    "status" => 400,
                    "message" => "You don't have this role"
                ];
            }
        }


        return [
            "ok" => true,
        ];
    }
    public function checkManager($id)
    {
        $user  = User::where(["id" => $id])->first();
        if (!$user) {
            return [
                "ok" => false,
                "status" => 400,
                "message" => "Manager does not exists."
            ];
        }

        if ($user->business_id != auth()->user()->business_id) {
            return [
                "ok" => false,
                "status" => 403,
                "message" => "Manager belongs to another business."
            ];
        }
        if (!$user->hasRole(("business_admin" . "#" . auth()->user()->business_id))) {
            return [
                "ok" => false,
                "status" => 403,
                "message" => "The user is not a manager"
            ];
        }
        return [
            "ok" => true,
        ];
    }

    public function checkEmployees($ids)
    {
        $users = User::whereIn("id", $ids)->get();
        foreach ($users as $user) {
            if (!$user) {
                return [
                    "ok" => false,
                    "status" => 400,
                    "message" => "Employee does not exists."
                ];
            }

            if ($user->business_id != auth()->user()->business_id) {
                return [
                    "ok" => false,
                    "status" => 403,
                    "message" => "Employee belongs to another business."
                ];
            }

            if (!$user->hasRole(("business_owner" . "#" . auth()->user()->business_id)) && !$user->hasRole(("business_admin" . "#" . auth()->user()->business_id)) &&  !$user->hasRole(("business_employee" . "#" . auth()->user()->business_id))) {
                return [
                    "ok" => false,
                    "status" => 403,
                    "message" => "The user is not a employee"
                ];
            }
        }

        return [
            "ok" => true,
        ];
    }


    public function checkDepartment($id)
    {
        $department  = Department::where(["id" => $id])->first();
        if (!$department) {
            return [
                "ok" => false,
                "status" => 400,
                "message" => "Department does not exists."
            ];
        }

        if ($department->business_id != auth()->user()->business_id) {
            return [
                "ok" => false,
                "status" => 403,
                "message" => "Department belongs to another business."
            ];
        }
        return [
            "ok" => true,
        ];
    }
    public function checkDepartments($ids)
    {
        $departments = Department::whereIn("id", $ids)->get();

        foreach ($departments as $department) {
            if (!$department) {
                return [
                    "ok" => false,
                    "status" => 400,
                    "message" => "Department does not exists."
                ];
            }

            if ($department->business_id != auth()->user()->business_id) {
                return [
                    "ok" => false,
                    "status" => 403,
                    "message" => "Department belongs to another business."
                ];
            }
        }

        return [
            "ok" => true,
        ];
    }

    public function checkUsers($ids)
    {


        foreach ($ids as $id) {
            $user = User::where("id", $id)
                ->first();
            if (!$user) {
                return [
                    "ok" => false,
                    "status" => 400,
                    "message" => "User does not exists."
                ];
            }

            if (empty(auth()->user()->business_id)) {
                if (!empty($user->business_id)) {
                    return [
                        "ok" => false,
                        "status" => 403,
                        "message" => "User belongs to another business."
                    ];
                }
            } else {
                if ($user->business_id != auth()->user()->business_id) {
                    return [
                        "ok" => false,
                        "status" => 403,
                        "message" => "User belongs to another business."
                    ];
                }
            }
        }

        return [
            "ok" => true,
        ];
    }

    public function checkRoles($ids)
    {


        foreach ($ids as $id) {
            $role = Role::where("id", $id)
                ->first();
            if (!$role) {
                return [
                    "ok" => false,
                    "status" => 400,
                    "message" => "Department does not exists."
                ];
            }

            if (empty(auth()->user()->business_id)) {
                if (!(empty($role->business_id) || $role->is_default == 1)) {
                    return [
                        "ok" => false,
                        "status" => 403,
                        "message" => "Role belongs to another business."
                    ];
                }
            } else {
                if ($role->business_id != auth()->user()->business_id) {
                    return [
                        "ok" => false,
                        "status" => 403,
                        "message" => "Role belongs to another business."
                    ];
                }
            }
        }

        return [
            "ok" => true,
        ];
    }
    public function checkEmploymentStatuses($ids)
    {
        $employment_statuses = EmploymentStatus::whereIn("id", $ids)
            ->get();

        foreach ($employment_statuses as $employment_status) {
            if (!$employment_status) {
                return [
                    "ok" => false,
                    "status" => 400,
                    "message" => "Employment status does not exists."
                ];
            }

            if (auth()->user()->hasRole('superadmin')) {
                if (!(($employment_status->business_id == NULL) && ($employment_status->is_default == 1) && ($employment_status->is_active == 1))) {
                    return [
                        "ok" => false,
                        "status" => 403,
                        "message" => "Employment status belongs to another business."
                    ];
                }
            }
            if (!auth()->user()->hasRole('superadmin')) {
                if (!(($employment_status->business_id == auth()->user()->business_id) && ($employment_status->is_default == 0) && ($employment_status->is_active == 1))) {
                    return [
                        "ok" => false,
                        "status" => 403,
                        "message" => "Employment status belongs to another business."
                    ];
                }
            }
        }

        return [
            "ok" => true,
        ];
    }

    //     public function storeDefaultsToBusiness($business_id,$business_name,$owner_id,$address_line_1) {


    //         Department::create([
    //             "name" => $business_name,
    //             "location" => $address_line_1,
    //             "is_active" => 1,
    //             "manager_id" => $owner_id,
    //             "business_id" => $business_id,
    //             "created_by" => $owner_id
    //         ]);


    //         $attached_defaults = [];
    //         $defaultRoles = Role::where([
    //             "business_id" => NULL,
    //             "is_default" => 1,
    //             "is_default_for_business" => 1,
    //             "guard_name" => "api",
    //           ])->get();

    //           foreach($defaultRoles as $defaultRole) {
    //               $insertableData = [
    //                 'name'  => ($defaultRole->name . "#" . $business_id),
    //                 "is_default" => 1,
    //                 "business_id" => $business_id,
    //                 "is_default_for_business" => 0,
    //                 "guard_name" => "api",
    //               ];
    //            $role  = Role::create($insertableData);
    //            $attached_defaults["roles"][$defaultRole->id] = $role->id;

    //            $permissions = $defaultRole->permissions;
    //            foreach ($permissions as $permission) {
    //                if(!$role->hasPermissionTo($permission)){
    //                    $role->givePermissionTo($permission);
    //                }
    //            }
    //           }




    //         $defaultDesignations = Designation::where([
    //             "business_id" => NULL,
    //             "is_default" => 1,
    //             "is_active" => 1
    //           ])->get();

    //           foreach($defaultDesignations as $defaultDesignation) {
    //               $insertableData = [
    //                 'name'  => $defaultDesignation->name,
    //                 'description'  => $defaultDesignation->description,
    //                 "is_active" => 1,
    //                 "is_default" => 1,
    //                 "business_id" => $business_id,
    //               ];
    //            $designation  = Designation::create($insertableData);
    //            $attached_defaults["designations"][$defaultDesignation->id] = $designation->id;
    //           }

    //           $defaultEmploymentStatuses = EmploymentStatus::where([
    //             "business_id" => NULL,
    //             "is_active" => 1,
    //             "is_default" => 1
    //           ])->get();

    //           foreach($defaultEmploymentStatuses as $defaultEmploymentStatus) {
    //               $insertableData = [
    //                 'name'  => $defaultEmploymentStatus->name,
    //                 'color'  => $defaultEmploymentStatus->color,
    //                 'description'  => $defaultEmploymentStatus->description,
    //                 "is_active" => 1,
    //                 "is_default" => 1,
    //                 "business_id" => $business_id,
    //               ];
    //            $employment_status  = EmploymentStatus::create($insertableData);
    //            $attached_defaults["employment_statuses"][$defaultEmploymentStatus->id] = $employment_status->id;
    //           }

    // // load setting leave
    //           $defaultSettingLeaves = SettingLeave::where([
    //             "business_id" => NULL,
    //             "is_active" => 1,
    //             "is_default" => 1
    //           ])->get();

    //           foreach($defaultSettingLeaves as $defaultSettingLeave) {
    //               $insertableData = [
    //                 'start_month' => $defaultSettingLeave->start_month,
    //                 'approval_level' => $defaultSettingLeave->approval_level,
    //                 'allow_bypass' => $defaultSettingLeave->allow_bypass,
    //                 "created_by" => auth()->user()->id,
    //                 "is_active" => 1,
    //                 "is_default" => 0,
    //                 "business_id" => $business_id,
    //               ];

    //            $setting_leave  = SettingLeave::create($insertableData);
    //            $attached_defaults["setting_leaves"][$defaultSettingLeave->id] = $setting_leave->id;


    //            $default_special_roles = $defaultSettingLeave->special_roles()->pluck("role_id");
    //            $special_roles_for_business = $default_special_roles->map(function ($id) use ($attached_defaults) {
    //             return $attached_defaults["roles"][$id];
    // });
    //            $setting_leave->special_roles()->sync($special_roles_for_business,[]);


    //     $default_paid_leave_employment_statuses = $defaultSettingLeave->paid_leave_employment_statuses()->pluck("employment_status_id");
    //            $paid_leave_employment_statuses_for_business = $default_paid_leave_employment_statuses->map(function ($id) use ($attached_defaults) {
    //             return $attached_defaults["employment_statuses"][$id];
    // });
    //            $setting_leave->paid_leave_employment_statuses()->sync($paid_leave_employment_statuses_for_business,[]);



    //            $default_unpaid_leave_employment_statuses = $defaultSettingLeave->unpaid_leave_employment_statuses()->pluck("employment_status_id");
    //            $unpaid_leave_employment_statuses_for_business = $default_unpaid_leave_employment_statuses->map(function ($id) use ($attached_defaults) {
    //             return $attached_defaults["employment_statuses"][$id];
    //  });
    //            $setting_leave->unpaid_leave_employment_statuses()->sync($unpaid_leave_employment_statuses_for_business,[]);




    //           }

    // // end load setting leave



    // // load setting attendance
    // $defaultSettingAttendances = SettingAttendance::where([
    //     "business_id" => NULL,
    //     "is_active" => 1,
    //     "is_default" => 1
    //   ])->get();



    //   foreach($defaultSettingAttendances as $defaultSettingAttendance) {
    //       $insertableData = [
    //         'punch_in_time_tolerance' => $defaultSettingAttendance->punch_in_time_tolerance,
    //         'work_availability_definition'=> $defaultSettingAttendance->work_availability_definition,
    //         'punch_in_out_alert'=> $defaultSettingAttendance->punch_in_out_alert,
    //         'punch_in_out_interval'=> $defaultSettingAttendance->punch_in_out_interval,
    //         'alert_area'=> $defaultSettingAttendance->alert_area,
    //         'auto_approval'=> $defaultSettingAttendance->auto_approval,

    //         "created_by" => auth()->user()->id,
    //         "is_active" => 1,
    //         "is_default" => 0,
    //         "business_id" => $business_id,
    //       ];

    //    $setting_attendance  = SettingAttendance::create($insertableData);
    //    $attached_defaults["setting_attendances"][$defaultSettingAttendance->id] = $setting_attendance->id;


    //    $default_special_roles = $defaultSettingAttendance->special_roles()->pluck("role_id");
    //    $special_roles_for_business = $default_special_roles->map(function ($id) use ($attached_defaults) {
    //     return $attached_defaults["roles"][$id];
    // });
    //    $setting_attendance->special_roles()->sync($special_roles_for_business,[]);





    //   }

    // // end load setting attendance







    //     }



    public function loadDefaultSettingLeave($business_id = NULL) {
    // load setting leave
    $default_setting_leave_query = [
        "business_id" => NULL,
        "is_active" => 1,
        "is_default" => 1
    ];
    if(!auth()->user()->hasRole("superadmin")) {
        $default_setting_leave_query["is_default"] = 0;
        $default_setting_leave_query["created_by"] = auth()->user()->id;
    }

    $defaultSettingLeaves = SettingLeave::where($default_setting_leave_query)->get();
    foreach ($defaultSettingLeaves as $defaultSettingLeave) {
        $insertableData = [
            'start_month' => $defaultSettingLeave->start_month,
            'approval_level' => $defaultSettingLeave->approval_level,
            'allow_bypass' => $defaultSettingLeave->allow_bypass,
            "created_by" => auth()->user()->id,
            "is_active" => 1,
            "is_default" => 0,
            "business_id" => $business_id,
        ];

        $setting_leave  = SettingLeave::create($insertableData);

        $business_owner_role_id = Role::where([
            "name" => ("business_owner#" . $business_id)
        ])
        ->pluck("id");

        $setting_leave->special_roles()->sync($business_owner_role_id, []);


        $default_paid_leave_employment_statuses = $defaultSettingLeave->paid_leave_employment_statuses()->pluck("employment_status_id");
        $setting_leave->paid_leave_employment_statuses()->sync($default_paid_leave_employment_statuses, []);

        $default_unpaid_leave_employment_statuses = $defaultSettingLeave->unpaid_leave_employment_statuses()->pluck("employment_status_id");
        $setting_leave->unpaid_leave_employment_statuses()->sync($default_unpaid_leave_employment_statuses, []);
    }

    // end load setting leave
    }


    public function loadDefaultAttendance($business_id = NULL) {
          // load setting attendance

          $default_setting_attendance_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => 1
        ];
        if(!auth()->user()->hasRole("superadmin")) {
            $default_setting_attendance_query["is_default"] = 0;
            $default_setting_attendance_query["created_by"] = auth()->user()->id;
        }

          $defaultSettingAttendances = SettingAttendance::where($default_setting_attendance_query)->get();



        foreach ($defaultSettingAttendances as $defaultSettingAttendance) {
            $insertableData = [
                'punch_in_time_tolerance' => $defaultSettingAttendance->punch_in_time_tolerance,
                'work_availability_definition' => $defaultSettingAttendance->work_availability_definition,
                'punch_in_out_alert' => $defaultSettingAttendance->punch_in_out_alert,
                'punch_in_out_interval' => $defaultSettingAttendance->punch_in_out_interval,
                'alert_area' => $defaultSettingAttendance->alert_area,
                'auto_approval' => $defaultSettingAttendance->auto_approval,

                "created_by" => auth()->user()->id,
                "is_active" => 1,
                "is_default" => 0,
                "business_id" => $business_id,
            ];

            $setting_attendance  = SettingAttendance::create($insertableData);




            $business_owner_role_id = Role::where([
                "name" => ("business_owner#" . $business_id)
            ])
            ->pluck("id");
            $setting_attendance->special_roles()->sync($business_owner_role_id, []);
        }

        // end load setting attendance

    }

    public function storeDefaultsToBusiness($business_id, $business_name, $owner_id, $address_line_1)
    {


     $department =  Department::create([
            "name" => $business_name,
            "location" => $address_line_1,
            "is_active" => 1,
            "manager_id" => $owner_id,
            "business_id" => $business_id,
            "created_by" => $owner_id
        ]);

        DepartmentUser::create([
            "user_id" => $owner_id,
            "department_id" => $department->id
        ]);

        
        WorkLocation::create([
            'name' => ($business_name . " " . "Office"),
            "is_active" => 1,
            "is_default" => 1,
            "business_id" => $business_id,
            "created_by" => $owner_id
        ]);





        $attached_defaults = [];
        $defaultRoles = Role::where([
            "business_id" => NULL,
            "is_default" => 1,
            "is_default_for_business" => 1,
            "guard_name" => "api",
        ])->get();

        foreach ($defaultRoles as $defaultRole) {
            $insertableData = [
                'name'  => ($defaultRole->name . "#" . $business_id),
                "is_default" => 1,
                "business_id" => $business_id,
                "is_default_for_business" => 0,
                "guard_name" => "api",
            ];
            $role  = Role::create($insertableData);
            $attached_defaults["roles"][$defaultRole->id] = $role->id;

            $permissions = $defaultRole->permissions;
            foreach ($permissions as $permission) {
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }






       $this->loadDefaultSettingLeave($business_id);

       $this->loadDefaultAttendance($business_id);




       $default_work_shift_data = [
        'name' => 'default work shift',
        'type' => 'regular',
        'description' => '',
        'is_personal' => false,
        'break_type' => 'unpaid',
        'break_hours' => 1,
        "attendances_count" => 0,
        'details' => $business->times->toArray()
    ];

    $default_work_shift = WorkShift::create($default_work_shift_data);
    $default_work_shift->details()->createMany($default_work_shift_data['details']);







    }
}
