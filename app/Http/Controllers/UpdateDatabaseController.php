<?php

namespace App\Http\Controllers;

use App\Http\Utils\BasicEmailUtil;
use App\Http\Utils\SetupUtil;
use App\Models\Business;
use App\Models\EmailTemplate;
use App\Models\Module;
use App\Models\ServicePlan;
use App\Models\ServicePlanModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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


        error_log("template creating 1");
        // Insert all email templates at once
        EmailTemplate::insert($email_templates->toArray());
    }

    public function updateDatabase()
    {


        $i = 6;



        for ($i; $i <= 10; $i++) {
            // @@@@@@@@@@@@@@@@@@@@  number - 1 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 1) {
                $this->storeEmailTemplates();
            }
            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 2) {
                DB::statement("
        CREATE PROCEDURE AddColumnIfNotExists()
        BEGIN
            IF NOT EXISTS (
                SELECT *
                FROM information_schema.COLUMNS
                WHERE TABLE_NAME = 'businesses'
                AND COLUMN_NAME = 'number_of_employees_allowed'
            )
            THEN
                ALTER TABLE businesses ADD COLUMN number_of_employees_allowed INTEGER DEFAULT 0;
            END IF;
        END;
    ");

                DB::statement("CALL AddColumnIfNotExists();");
                DB::statement("DROP PROCEDURE AddColumnIfNotExists;");
            }

            // @@@@@@@@@@@@@@@@@@@@  number - 3 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 3) {

                $foreignKeys = [
                    'disabled_employment_statuses' => 'disabled_employment_statuses_business_id_foreign',
                    'disabled_setting_leave_types' => 'disabled_setting_leave_types_business_id_foreign',
                    'disabled_job_platforms' => 'disabled_job_platforms_business_id_foreign',
                    'disabled_job_types' => 'disabled_job_types_business_id_foreign',
                    'disabled_work_locations' => 'disabled_work_locations_business_id_foreign',
                    'disabled_recruitment_processes' => 'disabled_recruitment_processes_business_id_foreign',
                    'disabled_banks' => 'disabled_banks_business_id_foreign',
                    'disabled_termination_types' => 'disabled_termination_types_business_id_foreign',
                    'disabled_termination_reasons' => 'disabled_termination_reasons_business_id_foreign',
                ];

                foreach ($foreignKeys as $table => $foreignKey) {
                    $foreignKeyExists = DB::select(DB::raw("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_NAME = '{$table}'
                    AND CONSTRAINT_NAME = '{$foreignKey}'
                "));

                    if (!empty($foreignKeyExists)) {
                        try {
                            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$foreignKey}");
                        } catch (\Exception $e) {
                            // Log the error or handle it as needed
                            echo "Failed to drop foreign key '{$foreignKey}' on table '{$table}': " . $e->getMessage();
                        }
                    } else {
                        echo "Foreign key '{$foreignKey}' does not exist on table '{$table}'. Skipping...\n";
                    }
                }

                foreach ($foreignKeys as $table => $foreignKey) {
                    try {
                        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$foreignKey} FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE");
                    } catch (\Exception $e) {
                        // Log the error or handle it as needed
                        echo "Failed to add foreign key '{$foreignKey}' on table '{$table}': " . $e->getMessage();
                    }
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

            if ($i == 5) {
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
            } else {
                $this->setupServicePlan();
            }
        }


        return "ok";
    }
}
