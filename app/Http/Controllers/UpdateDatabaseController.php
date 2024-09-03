<?php

namespace App\Http\Controllers;

use App\Http\Utils\BasicEmailUtil;
use App\Http\Utils\SetupUtil;
use App\Models\Business;
use App\Models\EmailTemplate;
use App\Models\Module;
use App\Models\RecruitmentProcess;
use App\Models\ServicePlan;
use App\Models\ServicePlanModule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateDatabaseController extends Controller
{
    use BasicEmailUtil, SetupUtil;

    private function storeEmailTemplates()
    {



        // Prepare initial email templates
        $email_templates = collect([
            $this->prepareEmailTemplateData("business_welcome_mail", NULL),
            $this->prepareEmailTemplateData("email_verification_mail", NULL),
            $this->prepareEmailTemplateData("reset_password_mail", NULL),
            $this->prepareEmailTemplateData("send_password_mail", NULL),
            $this->prepareEmailTemplateData("job_application_received_mail", NULL),

        ]);

        // Fetch business IDs and prepare business-specific email templates
        $business_email_templates = Business::pluck("id")->flatMap(function ($business_id) {
            return [
                $this->prepareEmailTemplateData("reset_password_mail", $business_id),
                $this->prepareEmailTemplateData("send_password_mail", $business_id),
                $this->prepareEmailTemplateData("job_application_received_mail", $business_id),

            ];
        });

        // Combine the two collections
        $email_templates = $email_templates->merge($business_email_templates);


        // Insert all email templates at once
        EmailTemplate::upsert(
            $email_templates->toArray(),
            ['type', 'business_id'], // Columns that determine uniqueness
            [
            "name",
            // "type",
            "template",
            "is_active",
            "is_default",
            // "business_id",
            'wrapper_id',
            "template_variables"
            ] // Columns to update if a match is found
        );

    }

    public function updateDatabase()
    {




        $i = 1;
        for ($i; $i <= 20; $i++) {

            if ($i == 1) {
                $modules = config("setup-config.system_modules");
                foreach ($modules as $module) {
                 $module_exists = Module::where([
                      "name" => $module
                    ])
                    ->exists();

                    if(!$module_exists) {
                      Module::create([
                          "name"=> $module,
                          "is_enabled" => 1,
                          'created_by' => 1,
                      ]);
                    }

                }

              }


            // @@@@@@@@@@@@@@@@@@@@  number - 1 @@@@@@@@@@@@@@@@@@@@@


            if ($i == 1) {
                $this->storeEmailTemplates();
            }
            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 2) {
                // Check and add the 'number_of_employees_allowed' column if it doesn't exist
                if (!Schema::hasColumn('businesses', 'number_of_employees_allowed')) {
                    DB::statement("ALTER TABLE businesses ADD COLUMN number_of_employees_allowed INTEGER DEFAULT 0");
                }
            }



            if ($i == 4) {

                // Define the table and foreign key
                $table = 'letter_templates';
                $foreignKey = 'letter_templates_business_id_foreign';

                // Check if the foreign key exists before trying to drop it
                $foreignKeyExists = DB::select(DB::raw("
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = '{$table}'
    AND CONSTRAINT_NAME = '{$foreignKey}'
"));

                if (!empty($foreignKeyExists)) {
                    // Drop the existing foreign key constraint
                    DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$foreignKey}");
                }

                // Modify the column to be nullable
                DB::statement("
    ALTER TABLE {$table}
    MODIFY COLUMN business_id BIGINT UNSIGNED NULL;
");

                // Re-add the foreign key constraint
                DB::statement("
    ALTER TABLE {$table}
    ADD CONSTRAINT {$foreignKey}
    FOREIGN KEY (business_id) REFERENCES businesses(id)
    ON DELETE CASCADE;
");
            }


            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 6) {
                // Check and add the 'in_geolocation' column if it doesn't exist
                if (!Schema::hasColumn('attendance_histories', 'in_geolocation')) {
                    DB::statement("ALTER TABLE attendance_histories ADD COLUMN in_geolocation VARCHAR(255) NULL");
                }

                // Check and add the 'out_geolocation' column if it doesn't exist
                if (!Schema::hasColumn('attendance_histories', 'out_geolocation')) {
                    DB::statement("ALTER TABLE attendance_histories ADD COLUMN out_geolocation VARCHAR(255) NULL");
                }
            }

            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 7) {
                // Check if the 'feedback' column exists
                if (Schema::hasColumn('candidates', 'feedback')) {
                    // Make the 'feedback' column nullable
                    DB::statement('ALTER TABLE candidates MODIFY feedback VARCHAR(255) NULL');
                }
            }
            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 8) {
                // // Check if the 'is_default' column exists
                // if (!Schema::hasColumn('asset_types', 'is_default')) {
                //     // Add the 'is_default' column as a boolean with a default value of 1
                //     DB::statement("ALTER TABLE asset_types ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 1");
                // }

                // Make the 'feedback' column nullable
                DB::statement('ALTER TABLE asset_types MODIFY business_id BIGINT(20) UNSIGNED NULL');


                $this->setupAssetTypes();
            }
            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 9) {
                if (Schema::hasColumn('comments', 'description')) {
                    DB::statement('ALTER TABLE comments MODIFY description LONGTEXT NULL');
                }
            }

            if ($i == 10) {
                EmailTemplate::where("type", "send_password_mail")->delete();
            }

            // @@@@@@@@@@@@@@@@@@@@  number - 3 @@@@@@@@@@@@@@@@@@@@@


            if ($i == 11) {
                $foreignKeys = [
                    'disabled_setting_leave_types' => 'disabled_setting_leave_types_business_id_foreign',
                    'disabled_task_categories' => 'disabled_task_categories_business_id_foreign',
                    'disabled_letter_templates' => 'disabled_letter_templates_business_id_foreign',
                    'disabled_asset_types' => 'disabled_asset_types_business_id_foreign',
                    'disabled_designations' => 'disabled_designations_business_id_foreign',
                    'disabled_employment_statuses' => 'disabled_employment_statuses_business_id_foreign',
                    'disabled_job_platforms' => 'disabled_job_platforms_business_id_foreign',
                    'disabled_job_types' => 'disabled_job_types_business_id_foreign',
                    'disabled_work_locations' => 'disabled_work_locations_business_id_foreign',
                    'disabled_recruitment_processes' => 'disabled_recruitment_processes_business_id_foreign',
                    'disabled_banks' => 'disabled_banks_business_id_foreign',
                    'disabled_termination_types' => 'disabled_termination_types_business_id_foreign',
                    'disabled_termination_reasons' => 'disabled_termination_reasons_business_id_foreign',
                ];

                // Disable foreign key checks to avoid errors during deletion
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                // Delete invalid records from tables
                foreach ($foreignKeys as $table => $foreignKey) {
                    // Delete records with invalid business_id
                    DB::statement("
              DELETE FROM {$table}
              WHERE business_id IS NOT NULL
              AND business_id NOT IN (SELECT id FROM businesses);
          ");
                }

                // Enable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                // Drop foreign key constraints if they exist
                foreach ($foreignKeys as $table => $foreignKey) {
                    try {
                        // Check if the foreign key exists before attempting to drop it
                        $foreignKeyExists = DB::select(DB::raw("
                  SELECT CONSTRAINT_NAME
                  FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_NAME = '{$table}'
                  AND CONSTRAINT_NAME = '{$foreignKey}'
              "));

                        if (!empty($foreignKeyExists)) {
                            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$foreignKey}");

                        } else {

                        }
                    } catch (\Exception $e) {
                        // Log the error or handle it as needed
                        echo "Failed to drop foreign key '{$foreignKey}' on table '{$table}': " . $e->getMessage();
                    }
                }

                // Re-add foreign key constraints
                foreach ($foreignKeys as $table => $foreignKey) {
                    try {
                        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$foreignKey} FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE");

                    } catch (\Exception $e) {
                        // Log the error or handle it as needed
                        echo "Failed to add foreign key '{$foreignKey}' on table '{$table}': " . $e->getMessage();
                    }
                }
            }

            if ($i == 13) {
                if (Schema::hasColumn('notifications', 'entity_name')) {
                    // Modify the column type to VARCHAR in MySQL
                    DB::statement('ALTER TABLE notifications MODIFY entity_name VARCHAR(255) NULL');
                }
                DB::statement("
                ALTER Table tasks
                MODIFY COLUMN task_category_id BIGINT UNSIGNED NULL;
            ");
            }






            if ($i == 12) {
                $modules = Module::where('is_enabled', 1)->pluck('id');

                $service_plan = ServicePlan::first(); // Retrieve the first service plan

                if ($service_plan) {
                    $service_plan->update([
                        'name' => 'Standard Plan',
                        'description' => '',
                        'set_up_amount' => 100,
                        'number_of_employees_allowed' => 100,
                        'duration_months' => 1,
                        'price' => 20,
                        'business_tier_id' => 1,
                        'created_by' => 1,
                    ]);

                    $service_plan_modules = $modules->map(function ($module_id) use ($service_plan) {
                        return [
                            'is_enabled' => 1,
                            'service_plan_id' => $service_plan->id,
                            'module_id' => $module_id,
                            'created_by' => 1,
                        ];
                    })->toArray();

                    ServicePlanModule::insert($service_plan_modules);
                } else {
                    $this->setupServicePlan();
                    $service_plan = ServicePlan::first(); // Retrieve the first service plan
                    if (empty($service_plan)) {
                         throw new Exception("service plan issues");
                    }

                }


                $businesses = Business::whereHas("owner")
                ->get(["id","owner_id","service_plan_id", "reseller_id","created_by"]);

                $this->defaultDataSetupForBusinessV2($businesses, $service_plan);


            }


        }




    return "ok";
    }
}
