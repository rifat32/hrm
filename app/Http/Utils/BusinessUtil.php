<?php

namespace App\Http\Utils;


use App\Models\Business;
use App\Models\BusinessTime;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\JobPlatform;
use App\Models\Role;
use App\Models\SettingAttendance;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\SettingPaymentDate;
use App\Models\SettingPayrun;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

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



    public function loadDefaultSettingLeave($business_id = NULL)
    {
        // load setting leave

        $default_setting_leave_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => auth()->user()->hasRole("superadmin") ? 1 : 0,
        ];

        if (!auth()->user()->hasRole("superadmin")) {
            $default_setting_leave_query["created_by"] = auth()->user()->id;
        }

        $defaultSettingLeaves = SettingLeave::where($default_setting_leave_query)->get();

        // If no records are found and the user is not a superadmin, retry without the 'created_by' condition
        if ($defaultSettingLeaves->isEmpty() && !auth()->user()->hasRole("superadmin")) {
            unset($default_setting_leave_query['created_by']);
            $defaultSettingLeaves = SettingLeave::where($default_setting_leave_query)->get();
        }





        foreach ($defaultSettingLeaves as $defaultSettingLeave) {
            error_log($defaultSettingLeave);
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


    public function loadDefaultAttendanceSetting($business_id = NULL)
    {
        // load setting attendance

        $default_setting_attendance_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => auth()->user()->hasRole("superadmin") ? 1 : 0,
        ];

        if (!auth()->user()->hasRole("superadmin")) {
            $default_setting_attendance_query["created_by"] = auth()->user()->id;
        }

        $defaultSettingAttendances = SettingAttendance::where($default_setting_attendance_query)->get();

        // If no records are found and the user is not a superadmin, retry without the 'created_by' condition
        if ($defaultSettingAttendances->isEmpty() && !auth()->user()->hasRole("superadmin")) {
            unset($default_setting_attendance_query['created_by']);
            $default_setting_attendance_query["is_default"] = 1;
            $defaultSettingAttendances = SettingAttendance::where($default_setting_attendance_query)->get();
        }







        foreach ($defaultSettingAttendances as $defaultSettingAttendance) {
            Log::info(json_encode($defaultSettingAttendance));
            $insertableData = [
                'punch_in_time_tolerance' => $defaultSettingAttendance->punch_in_time_tolerance,
                'work_availability_definition' => $defaultSettingAttendance->work_availability_definition,
                'punch_in_out_alert' => $defaultSettingAttendance->punch_in_out_alert,
                'punch_in_out_interval' => $defaultSettingAttendance->punch_in_out_interval,
                'alert_area' => $defaultSettingAttendance->alert_area,
                'auto_approval' => $defaultSettingAttendance->auto_approval,

                'service_name' => $defaultSettingAttendance->auto_approval,
                'api_key'=> $defaultSettingAttendance->auto_approval,

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
    public function loadDefaultPayrunSetting($business_id = NULL)
    {
        // load setting attendance

        $default_setting_payrun_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => auth()->user()->hasRole("superadmin") ? 1 : 0,
        ];

        if (!auth()->user()->hasRole("superadmin")) {
            $default_setting_payrun_query["created_by"] = auth()->user()->id;
        }

        $defaultSettingPayruns = SettingPayrun::where($default_setting_payrun_query)->get();

        // If no records are found and the user is not a superadmin, retry without the 'created_by' condition
        if ($defaultSettingPayruns->isEmpty() && !auth()->user()->hasRole("superadmin")) {
            unset($default_setting_payrun_query['created_by']);
            $defaultSettingPayruns = SettingPayrun::where($default_setting_payrun_query)->get();
        }


        foreach ($defaultSettingPayruns as $defaultSettingPayrun) {
            $insertableData = [
                'payrun_period' => $defaultSettingPayrun->payrun_period,
                'consider_type' => $defaultSettingPayrun->consider_type,
                'consider_overtime' => $defaultSettingPayrun->consider_overtime,

                "created_by" => auth()->user()->id,
                "is_active" => 1,
                "is_default" => 0,
                "business_id" => $business_id,
            ];

            $setting_payrun  = SettingPayrun::create($insertableData);




            //   $business_owner_role_id = Role::where([
            //       "name" => ("business_owner#" . $business_id)
            //   ])
            //   ->pluck("id");
            //   $setting_attendance->special_roles()->sync($business_owner_role_id, []);
        }
    }

    public function loadDefaultPaymentDateSetting($business_id = null)
{
    // Load default payment date settings

    $default_setting_payment_date_query = [
        'business_id' => null,
        'is_active' => 1,
        'is_default' => auth()->user()->hasRole('superadmin') ? 1 : 0,
    ];

    if (!auth()->user()->hasRole('superadmin')) {
        $default_setting_payment_date_query['created_by'] = auth()->user()->id;
    }

    $defaultSettingPaymentDates = SettingPaymentDate::where($default_setting_payment_date_query)->get();

    // If no records are found and the user is not a superadmin, retry without the 'created_by' condition
    if ($defaultSettingPaymentDates->isEmpty() && !auth()->user()->hasRole('superadmin')) {
        unset($default_setting_payment_date_query['created_by']);
        $defaultSettingPaymentDates = SettingPaymentDate::where($default_setting_payment_date_query)->get();
    }

    foreach ($defaultSettingPaymentDates as $defaultSettingPaymentDate) {
        $insertableData = [
            'payment_type' => $defaultSettingPaymentDate->payment_type,
            'day_of_week' => $defaultSettingPaymentDate->day_of_week,
            'day_of_month' => $defaultSettingPaymentDate->day_of_month,
            'custom_frequency_interval' => $defaultSettingPaymentDate->custom_frequency_interval,
            'custom_frequency_unit' => $defaultSettingPaymentDate->custom_frequency_unit,
            'notification_delivery_status' => $defaultSettingPaymentDate->notification_delivery_status,
            'is_active' => 1,
            'is_default' => 0,
            'business_id' => $business_id,
            'created_by' => auth()->user()->id,
            'role_specific_settings' => $defaultSettingPaymentDate->role_specific_settings,
        ];

        $settingPaymentDate = SettingPaymentDate::create($insertableData);

        // Additional logic can be added here if needed
    }
}

    // end load setting attendance

    public function storeDefaultsToBusiness($business_id, $business_name, $owner_id, $address_line_1, $business)
    {

        $work_location =  WorkLocation::create([
            'name' => ($business_name . " " . "Office"),
            "is_active" => 1,
            "is_default" => 1,
            "business_id" => $business_id,
            "created_by" => $owner_id
        ]);


        $department =  Department::create([
            "name" => $business_name,
            "location" => $address_line_1,
            "is_active" => 1,
            "manager_id" => $owner_id,
            "business_id" => $business_id,
            "work_location_id" => $work_location->id,
            "created_by" => $owner_id
        ]);

        DepartmentUser::create([
            "user_id" => $owner_id,
            "department_id" => $department->id
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

        $this->loadDefaultAttendanceSetting($business_id);

        $this->loadDefaultPayrunSetting($business_id);

        $this->loadDefaultPaymentDateSetting($business_id);




        $default_work_shift_data = [
            'name' => 'default work shift',
            'type' => 'regular',
            'description' => '',
            'is_personal' => false,
            'break_type' => 'unpaid',
            'break_hours' => 1,
            "attendances_count" => 0,
            'details' => $business->times->toArray(),
            "is_business_default" => 1,
            "is_active",
            "is_default" => 1,
            "business_id" => $business_id,
        ];

        $default_work_shift = WorkShift::create($default_work_shift_data);
        $default_work_shift->details()->createMany($default_work_shift_data['details']);
        $employee_work_shift_history_data = $default_work_shift->toArray();
        $employee_work_shift_history_data["work_shift_id"] = $default_work_shift->id;
        $employee_work_shift_history_data["from_date"] = $business->start_date;
        $employee_work_shift_history_data["to_date"] = NULL;
         $employee_work_shift_history =  WorkShiftHistory::create($employee_work_shift_history_data);
         $employee_work_shift_history->details()->createMany($default_work_shift_data['details']);
    }





    public function checkWorkShiftDetails($details)
    {

        foreach ($details as $index => $detail) {
            $business_time =   BusinessTime::where([
                "business_id" => auth()->user()->business_id,
                "day" => $detail["day"]
            ])
                ->first();
            if (!$business_time) {
                $error = [
                    "message" => "The given data was invalid.",
                    "errors" => [("details." . $index . ".day") => ["no business time found on this day"]]
                ];
                return [
                    "ok" => false,
                    "status" => 422,
                    "error" => $error
                ];
            }

            if ($business_time->is_weekend == 1 && $detail["is_weekend"] != 1) {
                $error = [
                    "message" => "The given data was invalid.",
                    "errors" => [("details." . $index . ".is_weekend") => ["This is weekend day"]]
                ];
                return [
                    "ok" => false,
                    "status" => 422,
                    "error" => $error
                ];
            }


            if (!empty($detail["start_at"]) && !empty($detail["end_at"] && !empty($business_time->start_at) && !empty($business_time->end_at))) {

                $request_start_at = Carbon::createFromFormat('H:i:s', $detail["start_at"]);
                $request_end_at = Carbon::createFromFormat('H:i:s', $detail["end_at"]);
                $business_start_at = Carbon::createFromFormat('H:i:s', $business_time->start_at);
                $business_end_at = Carbon::createFromFormat('H:i:s', $business_time->end_at);


                $difference_in_both_request  = $request_start_at->diffInHours($request_end_at);
                $difference_in_both_start_at  = $business_start_at->diffInHours($request_start_at);
                $difference_in_end_at_start_at  = $business_end_at->diffInHours($request_start_at);
                $difference_in_both_end_at  = $business_end_at->diffInHours($business_end_at);
                $difference_in_start_at_end_at  = $business_start_at->diffInHours($request_end_at);






                if ($difference_in_both_request < 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [
                            ("details." . $index . ".end_at") => ["end at should be greater than start at"]

                        ]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }


                if ($difference_in_both_start_at < 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("details." . $index . ".start_at") => ["start at should be in business working time $difference_in_both_start_at"]]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }



                if ($difference_in_end_at_start_at < 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("details." . $index . ".start_at") => ["start at should be in business working time"]]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }


                if ($difference_in_both_end_at > 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("details." . $index . ".end_at") => ["end at should be in business working time"]]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }

                if ($difference_in_start_at_end_at < 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("details." . $index . ".end_at") => ["end at should be in business working time"]]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }
            }
        }

        // foreach($request_data['details'] as $index => $detail) {
        //     $business_time =   BusinessTime::where([
        //            "business_id" => auth()->user()->business_id,
        //            "day" => $detail["day"]
        //        ])
        //        ->first();
        //        if(!$business_time) {
        //        $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [("details.".$index.".day")=>["no business time found on this day"]]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }


        //        if($business_time->is_weekend == 1 && $detail["is_weekend"] != 1) {
        //            $error = [
        //                    "message" => "The given data was invalid.",
        //                    "errors" => [("details.".$index.".is_weekend")=>["This is weekend day"]]
        //             ];
        //                throw new Exception(json_encode($error),422);
        //         }

        //         if(!empty($detail["start_at"]) && !empty($detail["end_at"] && !empty($business_time->start_at) && !empty($business_time->end_at)) ) {

        //        $request_start_at = Carbon::createFromFormat('H:i:s', $detail["start_at"]);
        //        $request_end_at = Carbon::createFromFormat('H:i:s', $detail["end_at"]);

        //        $business_start_at = Carbon::createFromFormat('H:i:s', $business_time->start_at);
        //        $business_end_at = Carbon::createFromFormat('H:i:s', $business_time->end_at);

        //        $difference_in_both_request  = $request_start_at->diffInHours($request_end_at);
        //        $difference_in_both_start_at  = $business_start_at->diffInHours($request_start_at);
        //        $difference_in_end_at_start_at  = $business_end_at->diffInHours($request_start_at);

        //        $difference_in_both_end_at  = $business_end_at->diffInHours($business_end_at);
        //        $difference_in_start_at_end_at  = $business_start_at->diffInHours($request_end_at);


        //        if($difference_in_both_request < 0) {
        //            $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [
        //                    ("details.".$index.".end_at")=>["end at should be greater than start at"]

        //                    ]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }


        //        if($difference_in_both_start_at < 0) {
        //            $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [ ("details.".$index.".start_at")=>["start at should be in business working time"]]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }



        //        if($difference_in_end_at_start_at < 0) {
        //         $error = [
        //             "message" => "The given data was invalid.",
        //             "errors" => [ ("details.".$index.".start_at")=>["start at should be in business working time"]]
        //      ];
        //         throw new Exception(json_encode($error),422);
        //     }


        //        if($difference_in_both_end_at > 0) {
        //            $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [ ("details.".$index.".end_at")=>["end at should be in business working time"]]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }

        //        if($difference_in_start_at_end_at < 0) {
        //            $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [ ("details.".$index.".end_at")=>["end at should be in business working time"]]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }
        //     }



        //    }

        return [
            "ok" => true,
        ];
    }
}
