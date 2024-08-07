<?php

namespace App\Http\Controllers;

use App\Http\Utils\BasicEmailUtil;
use App\Models\Business;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateDatabaseController extends Controller
{
    use BasicEmailUtil;

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


$i = 3;



for ($i; $i <= 100; $i++) {
    // @@@@@@@@@@@@@@@@@@@@  number - 1 @@@@@@@@@@@@@@@@@@@@@
   if ($i==1) {
    $this->storeEmailTemplates();
   }
    // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
    if ($i==2) {
        DB::statement('
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_name = \'businesses\'
                AND column_name = \'number_of_employees_allowed\'
            ) THEN
                ALTER TABLE businesses ADD COLUMN number_of_employees_allowed INTEGER DEFAULT 0;
            END IF;
        END $$;
    ');
       }
}
    // @@@@@@@@@@@@@@@@@@@@  number - 3 @@@@@@@@@@@@@@@@@@@@@
    if ($i==3) {

        DB::statement('ALTER TABLE disabled_employment_statuses DROP FOREIGN KEY disabled_employment_statuses_business_id_foreign');
        DB::statement('ALTER TABLE disabled_setting_leave_types DROP FOREIGN KEY disabled_setting_leave_types_business_id_foreign');
        DB::statement('ALTER TABLE disabled_job_platforms DROP FOREIGN KEY disabled_job_platforms_business_id_foreign');
        DB::statement('ALTER TABLE disabled_job_types DROP FOREIGN KEY disabled_job_types_business_id_foreign');
        DB::statement('ALTER TABLE disabled_work_locations DROP FOREIGN KEY disabled_work_locations_business_id_foreign');
        DB::statement('ALTER TABLE disabled_recruitment_processes DROP FOREIGN KEY disabled_recruitment_processes_business_id_foreign');
        DB::statement('ALTER TABLE disabled_banks DROP FOREIGN KEY disabled_banks_business_id_foreign');
        DB::statement('ALTER TABLE disabled_termination_types DROP FOREIGN KEY disabled_termination_types_business_id_foreign');
        DB::statement('ALTER TABLE disabled_termination_types DROP FOREIGN KEY disabled_termination_types_business_id_foreign');
        DB::statement('ALTER TABLE disabled_termination_reasons DROP FOREIGN KEY disabled_termination_reasons_business_id_foreign');




        DB::statement('ALTER TABLE disabled_employment_statuses ADD CONSTRAINT disabled_employment_statuses_business_id_foreign FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE disabled_setting_leave_types ADD CONSTRAINT disabled_setting_leave_types_business_id_foreign FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE disabled_job_platforms ADD CONSTRAINT disabled_job_platforms_business_id_foreign FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE disabled_job_types ADD CONSTRAINT disabled_job_types_business_id_foreign FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE disabled_work_locations ADD CONSTRAINT disabled_work_locations_business_id_foreign FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE disabled_recruitment_processes ADD CONSTRAINT disabled_recruitment_processes_business_id_foreign FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE disabled_banks ADD CONSTRAINT disabled_banks_business_id_foreign FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE disabled_termination_types ADD CONSTRAINT disabled_termination_types_business_id_foreign FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE disabled_termination_reasons ADD CONSTRAINT disabled_termination_reasons_business_id_foreign FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE');

       }


        return "ok";
    }





}
