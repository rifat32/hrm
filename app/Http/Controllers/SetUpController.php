<?php

namespace App\Http\Controllers;

use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\ActivityLog;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\ErrorLog;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\SocialSite;

class SetUpController extends Controller
{
    use ErrorUtil, UserActivityUtil;

    public function getErrorLogs(Request $request) {
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        $error_logs = ErrorLog::orderbyDesc("id")->paginate(10);
        return view("error-log",compact("error_logs"));
    }
    public function getActivityLogs(Request $request) {
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        $activity_logs = ActivityLog::orderbyDesc("id")->paginate(10);
        return view("user-activity-log",compact("activity_logs"));
    }

    public function migrate(Request $request) {
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        Artisan::call('migrate');
        return "migrated";
            }

    public function swaggerRefresh(Request $request) {
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
Artisan::call('l5-swagger:generate');
return "swagger generated";
    }

    public function setUp(Request $request)
    {
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        // @@@@@@@@@@@@@@@@@@@
        // clear everything
        // @@@@@@@@@@@@@@@@@@@

        Artisan::call('optimize:clear');
        Artisan::call('migrate:fresh');
        Artisan::call('migrate', ['--path' => 'vendor/laravel/passport/database/migrations']);
        Artisan::call('passport:install');
        Artisan::call('l5-swagger:generate');



        // ##########################################
        // user
        // #########################################
      $admin =  User::create([
        'first_Name' => "super",
        'last_Name'=> "admin",
        'phone'=> "01771034383",
        'address_line_1',
        'address_line_2',
        'country'=> "Bangladesh",
        'city'=> "Dhaka",
        'postcode'=> "1207",
        'email'=> "admin@gmail.com",
        'password'=>Hash::make("12345678@We"),
        "email_verified_at"=>now(),
        'is_active' => 1
        ]);
        $admin->email_verified_at = now();
        $admin->save();
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
            error_log($role_permission["role"]);
            $permissions = $role_permission["permissions"];
            $role->syncPermissions($permissions);
            // foreach ($permissions as $permission) {
            //     if(!$role->hasPermissionTo($permission)){
            //         $role->givePermissionTo($permission);
            //     }


            // }
        }






        $admin->assignRole("superadmin");


        $social_media_platforms = [
            [
                'name' => "Facebook",
                'icon' => "facebook",
                'link' => "https://www.facebook.com/",
            ],
            [
                'name' => "LinkedIn",
                'icon' => "linkedin",
                'link' => "https://www.linkedin.com/",
            ],
            [
                'name' => "Twitter",
                'icon' => "twitter",
                'link' => "https://twitter.com/",
            ],
            [
                'name' => "GitHub",
                'icon' => "github",
                'link' => "https://github.com/",
            ],
            [
                'name' => "Instagram",
                'icon' => "instagram",
                'link' => "https://www.instagram.com/",
            ],
            [
                'name' => "YouTube",
                'icon' => "youtube",
                'link' => "https://www.youtube.com/",
            ],
            // Add more social media platforms as needed
        ];

        // Iterate through the array and create records
        foreach ($social_media_platforms as $data) {
            SocialSite::create([
                'name' => $data['name'],
                'icon' => $data['icon'],
                'link' => $data['link'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }
        $default_designations = [
            [
                'name' => "CEO",
                'description' => "Chief Executive Officer",
            ],
            [
                'name' => "HR Manager",
                'description' => "Human Resources Manager",
            ],
            [
                'name' => "Finance Manager",
                'description' => "Finance Manager",
            ],
            [
                'name' => "Sales Representative",
                'description' => "Sales Representative",
            ],
            [
                'name' => "IT Specialist",
                'description' => "Information Technology Specialist",
            ],
            [
                'name' => "Marketing Coordinator",
                'description' => "Marketing Coordinator",
            ],
            [
                'name' => "Customer Service Representative",
                'description' => "Customer Service Representative",
            ],
        ];

        // Iterate through the array and create records
        foreach ($default_designations as $data) {
            Designation::create([
                'name' => $data['name'],
                'description' => $data['description'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }

        $default_employment_statuses = [
            [
                'name' => "Full-Time",
                'color' => "green",
                'description' => "Employee works the standard number of hours for a full-time position.",
            ],
            [
                'name' => "Part-Time",
                'color' => "blue",
                'description' => "Employee works fewer hours than a full-time position.",
            ],
            [
                'name' => "Contractor",
                'color' => "orange",
                'description' => "Employee is hired on a contractual basis for a specific project or duration.",
            ],
            [
                'name' => "Temporary",
                'color' => "yellow",
                'description' => "Employee is hired for a temporary period, often to cover a specific absence or workload.",
            ],
            [
                'name' => "Intern",
                'color' => "purple",
                'description' => "Employee is engaged in a temporary position for gaining practical work experience.",
            ],
        ];

        // Iterate through the array and create records
        foreach ($default_employment_statuses as $data) {
            EmploymentStatus::create([
                'name' => $data['name'],
                'color' => $data["color"],
                'description' => $data['description'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }
        $default_setting_leave_types = [
            [
                'name' => "Vacation Leave",
                'type' => "paid",
                'amount' => 80,
            ],
            [
                'name' => "Sick Leave",
                'type' => "paid",
                'amount' => 40,
            ],
            [
                'name' => "Personal Leave",
                'type' => "unpaid",
                'amount' => 30,
            ],
            [
                'name' => "Maternity Leave",
                'type' => "paid",
                'amount' => 120,
            ],
            [
                'name' => "Paternity Leave",
                'type' => "paid",
                'amount' => 80,
            ],
            [
                'name' => "Bereavement Leave",
                'type' => "paid",
                'amount' => 24,
            ],
        ];

        // Iterate through the array and create records
        foreach ($default_setting_leave_types as $data) {
            SettingLeaveType::create([
                'name' => $data['name'],
                'type' => $data["type"],
                'amount' => $data['amount'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }




        SettingLeave::create([
            'start_month' => 1,
            'approval_level' => "multiple",
            'allow_bypass' => 1,
          "business_id" => NULL,
          "is_active" => 1,
          "is_default" => 1,
          "created_by" => $admin->id,
        ]);


        return "You are done with setup";
    }


    public function roleRefresh(Request $request)
    {

        $this->storeActivity($request, "DUMMY activity","DUMMY description");
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
            error_log($role_permission["role"]);
            $permissions = $role_permission["permissions"];
            $role->syncPermissions($permissions);
        }

        return "You are done with setup";
    }


    public function backup(Request $request) {
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        foreach(DB::connection('backup_database')->table('users')->get() as $backup_data){

        $data_exists = DB::connection('mysql')->table('users')->where([
            "id" => $backup_data->id
           ])->first();
           if(!$data_exists) {
            DB::connection('mysql')->table('users')->insert(get_object_vars($backup_data));
           }
        }


        // foreach(DB::connection('backup_database')->table('automobile_categories')->get() as $backup_data){
        //     $data_exists = DB::connection('mysql')->table('automobile_categories')->where([
        //         "id" => $backup_data->id
        //        ])->first();
        //        if(!$data_exists) {
        //         DB::connection('mysql')->table('automobile_categories')->insert(get_object_vars($backup_data));
        //        }
        //     }

        //     foreach(DB::connection('backup_database')->table('automobile_makes')->get() as $backup_data){
        //         $data_exists = DB::connection('mysql')->table('automobile_makes')->where([
        //             "id" => $backup_data->id
        //            ])->first();
        //            if(!$data_exists) {
        //             DB::connection('mysql')->table('automobile_makes')->insert(get_object_vars($backup_data));
        //            }
        //         }

        //         foreach(DB::connection('backup_database')->table('automobile_models')->get() as $backup_data){
        //             $data_exists = DB::connection('mysql')->table('automobile_models')->where([
        //                 "id" => $backup_data->id
        //                ])->first();
        //                if(!$data_exists) {
        //                 DB::connection('mysql')->table('automobile_models')->insert(get_object_vars($backup_data));
        //                }
        //             }

        //             foreach(DB::connection('backup_database')->table('services')->get() as $backup_data){
        //                 $data_exists = DB::connection('mysql')->table('services')->where([
        //                     "id" => $backup_data->id
        //                    ])->first();
        //                    if(!$data_exists) {
        //                     DB::connection('mysql')->table('services')->insert(get_object_vars($backup_data));
        //                    }
        //                 }


        //                 foreach(DB::connection('backup_database')->table('sub_services')->get() as $backup_data){
        //                     $data_exists = DB::connection('mysql')->table('sub_services')->where([
        //                         "id" => $backup_data->id
        //                        ])->first();
        //                        if(!$data_exists) {
        //                         DB::connection('mysql')->table('sub_services')->insert(get_object_vars($backup_data));
        //                        }
        //                     }



                            foreach(DB::connection('backup_database')->table('businesses')->get() as $backup_data){
                                $data_exists = DB::connection('mysql')->table('businesses')->where([
                                    "id" => $backup_data->id
                                   ])->first();
                                   if(!$data_exists) {
                                    DB::connection('mysql')->table('businesses')->insert(get_object_vars($backup_data));
                                   }
                                }

                                foreach(DB::connection('backup_database')->table('business_automobile_makes')->get() as $backup_data){
                                    $data_exists = DB::connection('mysql')->table('business_automobile_makes')->where([
                                        "id" => $backup_data->id
                                       ])->first();
                                       if(!$data_exists) {
                                        DB::connection('mysql')->table('business_automobile_makes')->insert(get_object_vars($backup_data));
                                       }
                                    }

                                    foreach(DB::connection('backup_database')->table('business_automobile_models')->get() as $backup_data){
                                        $data_exists = DB::connection('mysql')->table('business_automobile_models')->where([
                                            "id" => $backup_data->id
                                           ])->first();
                                           if(!$data_exists) {
                                            DB::connection('mysql')->table('business_automobile_models')->insert(get_object_vars($backup_data));
                                           }
                                        }

                                        foreach(DB::connection('backup_database')->table('business_services')->get() as $backup_data){
                                            $data_exists = DB::connection('mysql')->table('business_services')->where([
                                                "id" => $backup_data->id
                                               ])->first();
                                               if(!$data_exists) {
                                                DB::connection('mysql')->table('business_services')->insert(get_object_vars($backup_data));
                                               }
                                            }

                                            foreach(DB::connection('backup_database')->table('business_sub_services')->get() as $backup_data){
                                                $data_exists = DB::connection('mysql')->table('business_sub_services')->where([
                                                    "id" => $backup_data->id
                                                   ])->first();
                                                   if(!$data_exists) {
                                                    DB::connection('mysql')->table('business_sub_services')->insert(get_object_vars($backup_data));
                                                   }
                                                }
                                                foreach(DB::connection('backup_database')->table('fuel_stations')->get() as $backup_data){
                                                    $data_exists = DB::connection('mysql')->table('fuel_stations')->where([
                                                        "id" => $backup_data->id
                                                       ])->first();
                                                       if(!$data_exists) {
                                                        DB::connection('mysql')->table('fuel_stations')->insert(get_object_vars($backup_data));
                                                       }
                                                    }

                                                return response()->json("done",200);
    }

}
