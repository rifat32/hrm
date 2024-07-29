<?php

namespace App\Http\Controllers;

use App\Http\Utils\BasicEmailUtil;
use App\Models\Business;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

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

        // Insert all email templates at once
        EmailTemplate::insert($email_templates->toArray());
    }

    public function updateDatabase()
    {
        // @@@@@@@@@@@@@@@@@@@@  number - 1 @@@@@@@@@@@@@@@@@@@@@
        $this->storeEmailTemplates();
    }
}
