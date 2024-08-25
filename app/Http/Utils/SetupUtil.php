<?php

namespace App\Http\Utils;

use App\Models\AssetType;
use App\Models\EmailTemplate;
use App\Models\Module;
use App\Models\Role;
use App\Models\ServicePlan;
use App\Models\ServicePlanModule;
use App\Models\SocialSite;
use App\Models\WorkLocation;
use Exception;
use Spatie\Permission\Models\Permission;

trait SetupUtil
{
    use BasicEmailUtil;

    public function storeEmailTemplates()
    {
        $email_templates = [
            $this->prepareEmailTemplateData("business_welcome_mail", NULL),
            $this->prepareEmailTemplateData("email_verification_mail", NULL),
            $this->prepareEmailTemplateData("reset_password_mail", NULL),
            $this->prepareEmailTemplateData("send_password_mail", NULL),
            $this->prepareEmailTemplateData("job_application_received_mail", NULL),
        ];
        error_log("template creating 4");
        EmailTemplate::insert($email_templates);
    }

    public function setupRoles()
    {

        // setup roles
        $roles = config("setup-config.roles");
        foreach ($roles as $role) {
            if (!Role::where([
                'name' => $role,
                'guard_name' => 'api',
                "is_system_default" => 1,
                "business_id" => NULL,
                "is_default" => 1,
            ])
                ->exists()) {
                Role::create([
                    'guard_name' => 'api',
                    'name' => $role,
                    "is_system_default" => 1,
                    "business_id" => NULL,
                    "is_default" => 1,
                    "is_default_for_business" => (in_array($role, [
                        "business_owner",
                        "business_admin",
                        "business_manager",
                        "business_employee"
                    ]) ? 1 : 0)


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

    public function setupAssetTypes() {

        $asset_types = [
            ['name' => 'Mobile Phone'],
            ['name' => 'Laptop']
        ];

        // Iterate through the array and create records only if they do not already exist
        foreach ($asset_types as $data) {
            // Check if a record with all the specified attributes exists
            $exists = AssetType::where([
                'name' => $data['name'],
                'is_active' => 1,
                'is_default' => 1,
                'business_id' => NULL,
                'created_by' => 1
            ])->exists();

            // Create the record if it does not exist
            if (!$exists) {
                AssetType::create([
                    'name' => $data['name'],
                    'is_active' => 1,
                    'is_default' => 1,
                    'business_id' => NULL,
                    'created_by' => 1
                ]);
            }
        }

    }

    public function setUpSocialMedia() {
        $social_media_platforms = [
            ['id' => 1, 'name' => 'Linkedin', 'icon' => 'FaLinkedin', 'link' => 'https://www.linkedin.com/'],
            ['id' => 2, 'name' => 'Github', 'icon' => 'FaGithub', 'link' => 'https://github.com/'],
            ['id' => 3, 'name' => 'Gitlab', 'icon' => 'FaGitlab', 'link' => 'https://gitlab.com/'],
            ['id' => 4, 'name' => 'Facebook', 'icon' => 'FaSquareFacebook', 'link' => 'https://www.facebook.com/'],
            ['id' => 5, 'name' => 'Instagram', 'icon' => 'FaInstagram', 'link' => 'https://www.instagram.com/'],
            ['id' => 6, 'name' => 'Youtube', 'icon' => 'FaYoutube', 'link' => 'https://www.youtube.com/'],
            ['id' => 7, 'name' => 'Twitter', 'icon' => 'FaSquareTwitter', 'link' => 'https://twitter.com/'],
            ['id' => 8, 'name' => 'Dribbble', 'icon' => 'FaSquareDribbble', 'link' => 'https://dribbble.com/'],
            ['id' => 9, 'name' => 'Behance', 'icon' => 'FaSquareBehance', 'link' => 'https://www.behance.net/'],
            ['id' => 10, 'name' => 'Twitch', 'icon' => 'FaTwitch', 'link' => 'https://www.twitch.tv/'],
            ['id' => 11, 'name' => 'Stack Overflow', 'icon' => 'FaStackOverflow', 'link' => 'https://stackoverflow.com/'],
            ['id' => 12, 'name' => 'Slack', 'icon' => 'FaSlack', 'link' => 'https://slack.com/'],
            ['id' => 13, 'name' => 'Other', 'icon' => 'FaGlobe', 'link' => ''],
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
                "created_by" => 1
            ]);
        }


    }
    public function roleRefreshFunc()
    {


        // ###############################
        // permissions
        // ###############################
        $permissions =  config("setup-config.permissions");

        // setup permissions
        foreach ($permissions as $permission) {
            if (!Permission::where([
                'name' => $permission,
                'guard_name' => 'api'
            ])
                ->exists()) {
                Permission::create(['guard_name' => 'api', 'name' => $permission]);
            }
        }
        // setup roles
        $roles = config("setup-config.roles");
        foreach ($roles as $role) {
            if (!Role::where([
                'name' => $role,
                'guard_name' => 'api',
                "is_system_default" => 1,
                "business_id" => NULL,
                "is_default" => 1,
            ])
                ->exists()) {
                Role::create([
                    'guard_name' => 'api',
                    'name' => $role,
                    "is_system_default" => 1,
                    "business_id" => NULL,
                    "is_default" => 1,
                    "is_default_for_business" => (in_array($role, [
                        "business_owner",
                        "business_admin",
                        "business_manager",
                        "business_employee"
                    ]) ? 1 : 0)


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



    public function setupServicePlan() {
        $modules = Module::where('is_enabled', 1)->pluck('id');

        $service_plan = ServicePlan::create([
            'name' => 'Standard Plan',
            'description' => '',
            'set_up_amount' => 100,
            'number_of_employees_allowed' => 100,
            'duration_months' => 1,
            'price' => 20,
            'business_tier_id' => 1,
            'created_by' => auth()->id(),
        ]);

        $service_plan_modules = $modules->map(function ($module_id) use ($service_plan) {
            return [
                'is_enabled' => 1,
                'service_plan_id' => $service_plan->id,
                'module_id' => $module_id,
                'created_by' => auth()->id(),
            ];
        })->toArray();

        ServicePlanModule::insert($service_plan_modules);

    }

    public function storeWorkLocation() {
        $default_work_location = [
            [
                'name' => "Office-Based",
                'description' => "Employees who work primarily at the company's physical office location."
            ],
            [
                'name' => "Remote",
                'description' => "Employees who work from a location outside the office, such as from home or another remote setting."
            ],
            [
                'name' => "Hybrid",
                'description' => "Employees who split their work time between the office and remote locations, following a flexible schedule."
            ],
            [
                'name' => "Client Site",
                'description' => "Employees who work primarily at the location of a client or customer."
            ],
            [
                'name' => "Field-Based",
                'description' => "Employees whose work involves traveling to various locations, such as sales representatives or field service technicians."
            ],
            [
                'name' => "On-Site",
                'description' => "Employees who work at a specific site or project location, but not necessarily the main office."
            ],
            [
                'name' => "Shop or Warehouse",
                'description' => "Employees working in a physical location where products are stored, manufactured, or distributed."
            ],
            [
                'name' => "Flexible Location",
                'description' => "Employees with the flexibility to choose their work location based on the nature of their tasks or projects."
            ],
            // Add more work location types as needed
        ];





        // Iterate through the array and create records
        foreach ($default_work_location as $data) {
            WorkLocation::create([
                'name' => $data['name'],
                'description' => $data['description'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => 1
            ]);
        }
    }



    public function loadDefaultAssetTypes() {

    }
    public function getDefaultAssetTypes() {

        $default_data = AssetType::where([
           "is_active" => 1,
           "is_default" => 1,
           "business_id" => NULL,
           "parent_id" => NULL
        ])

        ->get();

    }


    public function defaultDataSetupForBusiness($businesses) {




    }

}
