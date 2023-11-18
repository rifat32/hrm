<?php

namespace App\Http\Utils;


use App\Models\Business;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\JobPlatform;
use App\Models\Role;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\User;
use Exception;

trait BusinessUtil
{
    // this function do all the task and returns transaction id or -1



    public function businessOwnerCheck($business_id) {

        $businessQuery  = Business::where(["id" => $business_id]);
        if(!auth()->user()->hasRole('superadmin')) {
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
    public function checkLeaveType($id) {
        $setting_leave_type  = SettingLeaveType::where(["id" => $id])->first();
        if (!$setting_leave_type){
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

    public function checkUser($id) {
        $user  = User::where(["id" => $id])->first();
        if (!$user){
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
    public function checkRole($role) {

        if(!empty(auth()->user()->business_id)) {
            $role = $role . "#" . auth()->user()->business_id;
        }


        $role  = Role::where(["name" => $role])->first();
        if (!$role){
            return [
                "ok" => false,
                "status" => 400,
                "message" => "Role does not exists."
            ];
        }


        return [
            "ok" => true,
        ];
    }
    public function checkManager($id) {
        $user  = User::where(["id" => $id])->first();
        if (!$user){
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
        if (!$user->hasRole("business_admin")){
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

    public function checkEmployees($ids) {
        $users = User::whereIn("id", $ids)->get();
        foreach ($users as $user) {
            if (!$user){
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
            if (!$user->hasRole(("business_owner" . "#" . auth()->user()->business_id)) && !$user->hasRole(("business_admin" . "#" . auth()->user()->business_id)) &&  !$user->hasRole(("business_employee" . "#" . auth()->user()->business_id))){
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


    public function checkDepartment($id) {
        $department  = Department::where(["id" => $id])->first();
        if (!$department){
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
    public function checkDepartments($ids) {
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

    public function checkUsers($ids,$is_admin) {
        $users = User::whereIn("id", $ids)
        ->get();

        foreach ($users as $user) {
            if (!$user) {
                return [
                    "ok" => false,
                    "status" => 400,
                    "message" => "User does not exists."
                ];
            }

            if($is_admin) {
                if ($user->business_id != NULL) {
                    return [
                        "ok" => false,
                        "status" => 403,
                        "message" => "User belongs to another business."
                    ];
                }
            }
            if(!$is_admin) {
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

    public function checkRoles($ids,$is_admin) {
        $roles = Role::whereIn("id", $ids)
        ->get();

        foreach ($roles as $role) {
            if (!$role) {
                return [
                    "ok" => false,
                    "status" => 400,
                    "message" => "Department does not exists."
                ];
            }

            if($is_admin) {
                if (!(($role->business_id == NULL) && ($role->is_default == 1))) {
                    return [
                        "ok" => false,
                        "status" => 403,
                        "message" => "Role belongs to another business."
                    ];
                }
            }
            if(!$is_admin) {
                if (!(($role->business_id == auth()->user()->business_id) && ($role->is_default == 0))) {
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
    public function checkEmploymentStatuses($ids,$is_admin) {
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

            if($is_admin) {
                if (!(($employment_status->business_id == NULL) && ($employment_status->is_default == 1) && ($employment_status->is_active == 1) )) {
                    return [
                        "ok" => false,
                        "status" => 403,
                        "message" => "Employment status belongs to another business."
                    ];
                }
            }
            if(!$is_admin) {
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

    public function storeDefaultsToBusiness($business_id) {

        $attached_defaults = [];


        $defaultRoles = Role::where([
            "business_id" => NULL,
            "is_default" => 1,
            "is_default_for_business" => 1,
            "guard_name" => "api",
          ])->get();

          foreach($defaultRoles as $defaultRole) {
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
               if(!$role->hasPermissionTo($permission)){
                   $role->givePermissionTo($permission);
               }
           }
          }


          $defaultJobPlatforms = JobPlatform::where([
            "business_id" => NULL,
            "is_default" => 1,
            "is_active" => 1
          ])->get();

          foreach($defaultJobPlatforms as $defaultJobPlatform) {
              $insertableData = [
                'name'  => $defaultJobPlatform->name,
                'description'  => $defaultJobPlatform->description,
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => $business_id,
              ];
           $job_platform  = JobPlatform::create($insertableData);
           $attached_defaults["job_platforms"][$defaultJobPlatform->id] = $job_platform->id;
          }


        $defaultDesignations = Designation::where([
            "business_id" => NULL,
            "is_default" => 1,
            "is_active" => 1
          ])->get();

          foreach($defaultDesignations as $defaultDesignation) {
              $insertableData = [
                'name'  => $defaultDesignation->name,
                'description'  => $defaultDesignation->description,
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => $business_id,
              ];
           $designation  = Designation::create($insertableData);
           $attached_defaults["designations"][$defaultDesignation->id] = $designation->id;
          }

          $defaultEmploymentStatuses = EmploymentStatus::where([
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => 1
          ])->get();

          foreach($defaultEmploymentStatuses as $defaultEmploymentStatus) {
              $insertableData = [
                'name'  => $defaultEmploymentStatus->name,
                'color'  => $defaultEmploymentStatus->color,
                'description'  => $defaultEmploymentStatus->description,
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => $business_id,
              ];
           $employment_status  = EmploymentStatus::create($insertableData);
           $attached_defaults["employment_statuses"][$defaultEmploymentStatus->id] = $employment_status->id;
          }


          $defaultSettingLeaves = SettingLeave::where([
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => 1
          ])->get();

          foreach($defaultSettingLeaves as $defaultSettingLeave) {
              $insertableData = [
                'start_month' => $defaultSettingLeave->start_month,
                'approval_level' => $defaultSettingLeave->approval_level,
                'allow_bypass' => $defaultSettingLeave->allow_bypass,
                "is_active" => 1,
                "is_default" => 0,
                "business_id" => $business_id,
              ];

           $setting_leave  = SettingLeave::create($insertableData);
           $attached_defaults["setting_leaves"][$defaultSettingLeave->id] = $setting_leave->id;


           $default_special_roles = $defaultSettingLeave->special_roles()->pluck("role_id");
           $special_roles_for_business = $default_special_roles->map(function ($id) use ($attached_defaults) {
            return $attached_defaults["roles"][$id];
});
           $setting_leave->special_roles()->sync($special_roles_for_business,[]);


    $default_paid_leave_employment_statuses = $defaultSettingLeave->paid_leave_employment_statuses()->pluck("employment_status_id");
           $paid_leave_employment_statuses_for_business = $default_paid_leave_employment_statuses->map(function ($id) use ($attached_defaults) {
            return $attached_defaults["employment_statuses"][$id];
});
           $setting_leave->paid_leave_employment_statuses()->sync($paid_leave_employment_statuses_for_business,[]);



           $default_unpaid_leave_employment_statuses = $defaultSettingLeave->unpaid_leave_employment_statuses()->pluck("employment_status_id");
           $unpaid_leave_employment_statuses_for_business = $default_unpaid_leave_employment_statuses->map(function ($id) use ($attached_defaults) {
            return $attached_defaults["employment_statuses"][$id];
 });
           $setting_leave->unpaid_leave_employment_statuses()->sync($unpaid_leave_employment_statuses_for_business,[]);




          }












    }



}
