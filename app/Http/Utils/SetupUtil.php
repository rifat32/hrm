<?php

namespace App\Http\Utils;

use App\Models\EmailTemplate;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
trait SetupUtil
{
use BasicEmailUtil;

    public function storeEmailTemplates() {
        $email_templates = [
            $this->prepareEmailTemplateData("business_welcome_mail",NULL),
            $this->prepareEmailTemplateData("email_verification_mail",NULL),
            $this->prepareEmailTemplateData("reset_password_mail",NULL),
            $this->prepareEmailTemplateData("send_password_mail",NULL),
            $this->prepareEmailTemplateData("job_application_received_mail", NULL),
        ];
        error_log("template creating 4");
        EmailTemplate::insert($email_templates);

    }

    public function setupRoles () {
          // setup roles
          $roles = config("setup-config.roles");
          foreach ($roles as $role) {
              if(!Role::where([
              'name' => $role,
              'guard_name' => 'api',
              "is_system_default" => 1,
              "business_id" => NULL,
              "is_default" => 1,
              ])
              ->exists()){
               Role::create(['guard_name' => 'api', 'name' => $role,"is_system_default"=> 1, "business_id" => NULL,
               "is_default" => 1,
               "is_default_for_business" => (in_array($role ,["business_owner",
               "business_admin",
               "business_manager",
               "business_employee"])?1:0)


              ]);
              }

          }

          // setup roles and permissions
          $role_permissions = config("setup-config.roles_permission");
          foreach ($role_permissions as $role_permission) {
              $role = Role::where(["name" => $role_permission["role"]])->first();
              // error_log($role_permission["role"]);
              $permissions = $role_permission["permissions"];
              $role->syncPermissions($permissions);
              // foreach ($permissions as $permission) {
              //     if(!$role->hasPermissionTo($permission)){
              //         $role->givePermissionTo($permission);
              //     }


              // }
          }
    }
    public function roleRefreshFunc(){


   // ###############################
        // permissions
        // ###############################
        $permissions =  config("setup-config.permissions");

        // setup permissions
        foreach ($permissions as $permission) {
            if(!Permission::where([
            'name' => $permission,
            'guard_name' => 'api'
            ])
            ->exists()){
                Permission::create(['guard_name' => 'api', 'name' => $permission]);
            }

        }
        // setup roles
        $roles = config("setup-config.roles");
        foreach ($roles as $role) {
            if(!Role::where([
            'name' => $role,
            'guard_name' => 'api',
            "is_system_default" => 1,
            "business_id" => NULL,
            "is_default" => 1,
            ])
            ->exists()){
             Role::create(['guard_name' => 'api', 'name' => $role,"is_system_default"=> 1, "business_id" => NULL,
             "is_default" => 1,
             "is_default_for_business" => (in_array($role ,["business_owner",
             "business_admin",
             "business_manager",
             "business_employee"])?1:0)


            ]);
            }
        }


// setup roles and permissions
$role_permissions = config("setup-config.roles_permission");
foreach ($role_permissions as $role_permission) {
    $role = Role::where(["name" => $role_permission["role"]])->first();

    $permissions = $role_permission["permissions"];

    // Get current permissions associated with the role
    $currentPermissions = $role->permissions()->pluck('name')->toArray();

    // Determine permissions to remove
    $permissionsToRemove = array_diff($currentPermissions, $permissions);

    // Deassign permissions not included in the configuration
    if (!empty($permissionsToRemove)) {
        foreach ($permissionsToRemove as $permission) {
            $role->revokePermissionTo($permission);
        }
    }

    // Assign permissions from the configuration
    $role->syncPermissions($permissions);
}


// $business_ids = Business::get()->pluck("id");

// foreach ($role_permissions as $role_permission) {

//     if($role_permission["role"] == "business_employee"){
//         foreach($business_ids as $business_id){

//             $role = Role::where(["name" => $role_permission["role"] . "#" . $business_id])->first();

//            if(empty($role)){

//             continue;
//            }

//                 $permissions = $role_permission["permissions"];

//                 // Assign permissions from the configuration
//     $role->syncPermissions($permissions);



//         }

//     }

//     if($role_permission["role"] == "business_manager"){
//         foreach($business_ids as $business_id){

//             $role = Role::where(["name" => $role_permission["role"] . "#" . $business_id])->first();

//            if(empty($role)){

//             continue;
//            }

//                 $permissions = $role_permission["permissions"];

//                 // Assign permissions from the configuration
//     $role->syncPermissions($permissions);



//         }

//     }



// }
    }

}
